<?php

namespace App\Domain\Seating\Support;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanRow;
use App\Domain\Seating\Models\SeatPlanSeat;

/**
 * Full-state replace for the normalized seat-plan tree. Consumes the payload
 * shape produced by the editor and reconciles it against the current DB
 * state: persists new entities, updates existing ones (matched by `id`), and
 * deletes anything no longer referenced. Returns a map of client-provided
 * ids → persisted PKs so the client can rewrite its `new-*` placeholders.
 *
 * @see docs/mil-std-498/SDD.md §5.3c Seating
 */
class SeatPlanTreeSyncer
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<int, array<string, mixed>>  $planLabels  Plan-level (un-blocked) labels (SET-F-020).
     * @return array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}
     */
    public function sync(SeatPlan $plan, array $blocks, array $planLabels = []): array
    {
        $idMap = UpdateSeatPlanResult::emptyIdMap();

        $existingBlockIds = $plan->blocks()->pluck('id')->all();
        $seenBlockIds = [];

        foreach ($blocks as $index => $blockPayload) {
            $clientBlockId = $this->clientId($blockPayload['id'] ?? null);
            $rawPrefix = $blockPayload['seat_title_prefix'] ?? null;
            $prefix = is_string($rawPrefix) && $rawPrefix !== '' ? $rawPrefix : null;

            $blockAttributes = [
                'seat_plan_id' => $plan->id,
                'title' => (string) ($blockPayload['title'] ?? ''),
                'color' => (string) ($blockPayload['color'] ?? '#2c3e50'),
                'seat_title_prefix' => $prefix,
                'background_image_url' => $blockPayload['background_image_url'] ?? null,
                'sort_order' => (int) ($blockPayload['sort_order'] ?? $index),
            ];

            $block = $this->upsertBlock($plan, $blockPayload['id'] ?? null, $blockAttributes);
            $seenBlockIds[] = $block->id;

            if ($clientBlockId !== null) {
                $idMap['blocks'][$clientBlockId] = $block->id;
            }

            $this->syncRestrictions($block, $blockPayload['allowed_ticket_category_ids'] ?? []);

            /* Rows are upserted first so seats can resolve their row_id
             * references (which may still be `new-row-*` client placeholders
             * on first save) against the persisted PKs. */
            $rowMap = $this->syncRows($block, $blockPayload['rows'] ?? [], $idMap);
            $this->syncBlockSeats($block, $blockPayload['seats'] ?? [], $rowMap, $idMap);
            $this->syncLabels($block, $blockPayload['labels'] ?? [], $idMap);
        }

        $removedBlockIds = array_values(array_diff($existingBlockIds, $seenBlockIds));
        if ($removedBlockIds !== []) {
            SeatPlanBlock::query()->whereIn('id', $removedBlockIds)->delete();
        }

        $this->syncPlanLabels($plan, $planLabels, $idMap);

        return $idMap;
    }

    /**
     * Reconcile plan-level (un-blocked) labels. Mirrors `syncLabels` but for
     * labels with `seat_plan_block_id = NULL` (SET-F-020).
     *
     * @param  array<int, array<string, mixed>>  $labels
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     */
    private function syncPlanLabels(SeatPlan $plan, array $labels, array &$idMap): void
    {
        $existingLabelIds = $plan->globalLabels()->pluck('id')->all();
        $seenLabelIds = [];

        foreach ($labels as $index => $labelPayload) {
            $clientLabelId = $this->clientId($labelPayload['id'] ?? null);
            $attributes = [
                'seat_plan_id' => $plan->id,
                'seat_plan_block_id' => null,
                'title' => (string) ($labelPayload['title'] ?? ''),
                'x' => (int) ($labelPayload['x'] ?? 0),
                'y' => (int) ($labelPayload['y'] ?? 0),
                'sort_order' => (int) ($labelPayload['sort_order'] ?? $index),
            ];

            $id = $labelPayload['id'] ?? null;
            $label = null;

            if (is_numeric($id)) {
                $label = SeatPlanLabel::query()
                    ->where('seat_plan_id', $plan->id)
                    ->whereNull('seat_plan_block_id')
                    ->whereKey((int) $id)
                    ->first();

                if ($label !== null) {
                    $label->fill($attributes)->save();
                }
            }

            if ($label === null) {
                $label = SeatPlanLabel::query()->create($attributes);
            }

            $seenLabelIds[] = $label->id;

            if ($clientLabelId !== null) {
                $idMap['labels'][$clientLabelId] = $label->id;
            }
        }

        $removedLabelIds = array_values(array_diff($existingLabelIds, $seenLabelIds));
        if ($removedLabelIds !== []) {
            SeatPlanLabel::query()->whereIn('id', $removedLabelIds)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertBlock(SeatPlan $plan, mixed $id, array $attributes): SeatPlanBlock
    {
        if (is_numeric($id)) {
            $block = SeatPlanBlock::query()
                ->where('seat_plan_id', $plan->id)
                ->whereKey((int) $id)
                ->first();

            if ($block !== null) {
                $block->fill($attributes)->save();

                return $block;
            }
        }

        return SeatPlanBlock::query()->create($attributes);
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncRestrictions(SeatPlanBlock $block, array $categoryIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map(
            fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null,
            $categoryIds,
        ), fn (?int $id): bool => $id !== null)));

        $block->categoryRestrictions()->sync($ids);
    }

    /**
     * Upsert rows for a block. Returns a map from every row's inbound
     * identifier (both numeric PKs — mapped to themselves — and the
     * `new-row-*` client placeholders) to the persisted row PK, so
     * `syncBlockSeats` can resolve `seat.row_id` references afterwards.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     * @return array<string, int>
     */
    private function syncRows(SeatPlanBlock $block, array $rows, array &$idMap): array
    {
        $existingRowIds = $block->rows()->pluck('id')->all();
        $seenRowIds = [];
        $rowMap = [];

        foreach ($rows as $index => $rowPayload) {
            $clientRowId = $this->clientId($rowPayload['id'] ?? null);
            $row = $this->upsertRow($block, $rowPayload['id'] ?? null, [
                'seat_plan_block_id' => $block->id,
                'name' => (string) ($rowPayload['name'] ?? (string) ($index + 1)),
                'sort_order' => (int) ($rowPayload['sort_order'] ?? $index),
            ]);
            $seenRowIds[] = $row->id;

            if ($clientRowId !== null) {
                $idMap['rows'][$clientRowId] = $row->id;
                $rowMap[$clientRowId] = $row->id;
            }

            $rowMap[(string) $row->id] = $row->id;
        }

        $removedRowIds = array_values(array_diff($existingRowIds, $seenRowIds));
        if ($removedRowIds !== []) {
            SeatPlanRow::query()->whereIn('id', $removedRowIds)->delete();
        }

        return $rowMap;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertRow(SeatPlanBlock $block, mixed $id, array $attributes): SeatPlanRow
    {
        if (is_numeric($id)) {
            $row = SeatPlanRow::query()
                ->where('seat_plan_block_id', $block->id)
                ->whereKey((int) $id)
                ->first();

            if ($row !== null) {
                $row->fill($attributes)->save();

                return $row;
            }
        }

        return SeatPlanRow::query()->create($attributes);
    }

    /**
     * Upsert seats scoped to the block. The editor payload carries seats
     * flat at the block level with `row_id` references (which may still be
     * `new-row-*` client placeholders until `syncRows` has run and returned
     * `$rowMap`). Resolve the reference; fall back to `null` when unknown so
     * orphan seats still land in the block.
     *
     * @param  array<int, array<string, mixed>>  $seats
     * @param  array<string, int>  $rowMap
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     */
    private function syncBlockSeats(SeatPlanBlock $block, array $seats, array $rowMap, array &$idMap): void
    {
        $existingSeatIds = $block->seats()->pluck('id')->all();
        $seenSeatIds = [];

        foreach ($seats as $seatPayload) {
            $clientSeatId = $this->clientId($seatPayload['id'] ?? null);

            $rawRowId = $seatPayload['row_id'] ?? null;
            $rowPk = null;
            if ($rawRowId !== null) {
                $key = (string) $rawRowId;
                if (isset($rowMap[$key])) {
                    $rowPk = $rowMap[$key];
                }
            }

            $attributes = [
                'seat_plan_id' => $block->seat_plan_id,
                'seat_plan_block_id' => $block->id,
                'seat_plan_row_id' => $rowPk,
                'number' => isset($seatPayload['number']) && is_numeric($seatPayload['number']) ? (int) $seatPayload['number'] : null,
                'title' => (string) ($seatPayload['title'] ?? ''),
                'x' => (int) ($seatPayload['x'] ?? 0),
                'y' => (int) ($seatPayload['y'] ?? 0),
                'salable' => (bool) ($seatPayload['salable'] ?? true),
                'color' => $seatPayload['color'] ?? null,
                'note' => $seatPayload['note'] ?? null,
                'custom_data' => is_array($seatPayload['custom_data'] ?? null) ? $seatPayload['custom_data'] : null,
            ];

            $seat = $this->upsertSeat($block, $seatPayload['id'] ?? null, $attributes);
            $seenSeatIds[] = $seat->id;

            if ($clientSeatId !== null) {
                $idMap['seats'][$clientSeatId] = $seat->id;
            }
        }

        $removedSeatIds = array_values(array_diff($existingSeatIds, $seenSeatIds));
        if ($removedSeatIds !== []) {
            SeatPlanSeat::query()->whereIn('id', $removedSeatIds)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertSeat(SeatPlanBlock $block, mixed $id, array $attributes): SeatPlanSeat
    {
        if (is_numeric($id)) {
            $seat = SeatPlanSeat::query()
                ->where('seat_plan_block_id', $block->id)
                ->whereKey((int) $id)
                ->first();

            if ($seat !== null) {
                $seat->fill($attributes)->save();

                return $seat;
            }
        }

        return SeatPlanSeat::query()->create($attributes);
    }

    /**
     * @param  array<int, array<string, mixed>>  $labels
     * @param  array{blocks: array<string, int>, rows: array<string, int>, seats: array<string, int>, labels: array<string, int>}  $idMap
     */
    private function syncLabels(SeatPlanBlock $block, array $labels, array &$idMap): void
    {
        $existingLabelIds = $block->labels()->pluck('id')->all();
        $seenLabelIds = [];

        foreach ($labels as $index => $labelPayload) {
            $clientLabelId = $this->clientId($labelPayload['id'] ?? null);
            $attributes = [
                'seat_plan_id' => $block->seat_plan_id,
                'seat_plan_block_id' => $block->id,
                'title' => (string) ($labelPayload['title'] ?? ''),
                'x' => (int) ($labelPayload['x'] ?? 0),
                'y' => (int) ($labelPayload['y'] ?? 0),
                'sort_order' => (int) ($labelPayload['sort_order'] ?? $index),
            ];

            $label = $this->upsertLabel($block, $labelPayload['id'] ?? null, $attributes);
            $seenLabelIds[] = $label->id;

            if ($clientLabelId !== null) {
                $idMap['labels'][$clientLabelId] = $label->id;
            }
        }

        $removedLabelIds = array_values(array_diff($existingLabelIds, $seenLabelIds));
        if ($removedLabelIds !== []) {
            SeatPlanLabel::query()->whereIn('id', $removedLabelIds)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertLabel(SeatPlanBlock $block, mixed $id, array $attributes): SeatPlanLabel
    {
        if (is_numeric($id)) {
            $label = SeatPlanLabel::query()
                ->where('seat_plan_block_id', $block->id)
                ->whereKey((int) $id)
                ->first();

            if ($label !== null) {
                $label->fill($attributes)->save();

                return $label;
            }
        }

        return SeatPlanLabel::query()->create($attributes);
    }

    private function clientId(mixed $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if (is_string($id) && ! is_numeric($id)) {
            return $id;
        }

        return null;
    }
}
