<?php

namespace App\Domain\Seating\Support;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use App\Domain\Seating\Models\SeatPlanRow;
use App\Domain\Seating\Models\SeatPlanSeat;
use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;

/**
 * One-shot converter for legacy JSONB seat-plan data to the normalized
 * blocks → rows → seats tree. Shared by the inline data migration and the
 * observable `seating:migrate-json-to-normalized` Artisan command.
 *
 * Idempotent per plan: skips plans that already have normalized blocks. Legacy
 * zones/rows/seats JSON is converted to block-shaped data before writing so
 * the backfill matches the Vue wrapper's read-time conversion.
 *
 * @see docs/mil-std-498/SDD.md §5.3c.1 Data Model
 */
class LegacySeatPlanConverter
{
    /**
     * @return array{migrated: int, skipped: int, orphans: array<int, array<string, mixed>>}
     */
    public function backfillAll(): array
    {
        $migrated = 0;
        $skipped = 0;
        $orphans = [];

        /** @var iterable<int, SeatPlan> $plans */
        $plans = SeatPlan::query()->get();

        foreach ($plans as $plan) {
            if ($plan->blocks()->exists()) {
                $skipped++;

                continue;
            }

            $result = DB::transaction(fn () => $this->backfillPlan($plan));

            $migrated++;
            foreach ($result['orphans'] as $orphan) {
                $orphans[] = $orphan;
            }
        }

        return ['migrated' => $migrated, 'skipped' => $skipped, 'orphans' => $orphans];
    }

    /**
     * @return array{orphans: array<int, array<string, mixed>>}
     */
    public function backfillPlan(SeatPlan $plan): array
    {
        $data = $this->readLegacyData($plan);
        $blocks = $this->normaliseBlocks($data);

        $seatIdMap = [];
        $validCategoryIds = TicketCategory::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($blocks as $blockIndex => $blockPayload) {
            $block = SeatPlanBlock::query()->create([
                'seat_plan_id' => $plan->id,
                'title' => (string) ($blockPayload['title'] ?? "Block {$blockIndex}"),
                'color' => (string) ($blockPayload['color'] ?? '#2c3e50'),
                'background_image_url' => null,
                'sort_order' => $blockIndex,
            ]);

            $categoryIds = array_values(array_filter(array_map(
                fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null,
                (array) ($blockPayload['allowed_ticket_category_ids'] ?? []),
            ), fn (?int $id): bool => $id !== null && in_array($id, $validCategoryIds, true)));

            if ($categoryIds !== []) {
                $block->categoryRestrictions()->sync($categoryIds);
            }

            $defaultRow = SeatPlanRow::query()->create([
                'seat_plan_block_id' => $block->id,
                'name' => '(default)',
                'sort_order' => 0,
            ]);

            foreach ((array) ($blockPayload['seats'] ?? []) as $seatPayload) {
                $legacyId = $seatPayload['id'] ?? null;

                $customData = array_filter([
                    'color' => $seatPayload['color'] ?? null,
                    'note' => $seatPayload['note'] ?? null,
                    'custom_data' => $seatPayload['custom_data'] ?? null,
                    'legacy_id' => $legacyId,
                ], fn (mixed $v): bool => $v !== null);

                $seat = SeatPlanSeat::query()->create([
                    'seat_plan_id' => $plan->id,
                    'seat_plan_block_id' => $block->id,
                    'seat_plan_row_id' => $defaultRow->id,
                    'number' => null,
                    'title' => (string) ($seatPayload['title'] ?? 'Seat'),
                    'x' => (int) ($seatPayload['x'] ?? 0),
                    'y' => (int) ($seatPayload['y'] ?? 0),
                    'salable' => (bool) ($seatPayload['salable'] ?? true),
                    'color' => is_string($seatPayload['color'] ?? null) ? $seatPayload['color'] : null,
                    'note' => is_string($seatPayload['note'] ?? null) ? $seatPayload['note'] : null,
                    'custom_data' => $customData === [] ? null : $customData,
                ]);

                if ($legacyId !== null) {
                    $seatIdMap[(string) $legacyId] = $seat->id;
                }
            }

            foreach ((array) ($blockPayload['labels'] ?? []) as $labelIndex => $labelPayload) {
                SeatPlanLabel::query()->create([
                    'seat_plan_id' => $plan->id,
                    'seat_plan_block_id' => $block->id,
                    'title' => (string) ($labelPayload['title'] ?? ''),
                    'x' => (int) ($labelPayload['x'] ?? 0),
                    'y' => (int) ($labelPayload['y'] ?? 0),
                    'sort_order' => $labelIndex,
                ]);
            }
        }

        return ['orphans' => $this->relinkAssignments($plan, $seatIdMap)];
    }

    /**
     * @return array<string, mixed>
     */
    private function readLegacyData(SeatPlan $plan): array
    {
        $raw = DB::table('seat_plans')->where('id', $plan->id)->value('data');

        if ($raw === null) {
            return ['blocks' => []];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : ['blocks' => []];
        }

        if (is_array($raw)) {
            return $raw;
        }

        return ['blocks' => []];
    }

    /**
     * Normalise a legacy JSON payload to the `blocks: [{seats, labels, ...}]` shape.
     * Handles the legacy `zones` format (zones → rows → seats) the same way the
     * Vue wrapper used to.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normaliseBlocks(array $data): array
    {
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            return array_values($data['blocks']);
        }

        if (! isset($data['zones']) || ! is_array($data['zones'])) {
            return [];
        }

        $blocks = [];

        foreach ($data['zones'] as $zoneIndex => $zone) {
            $seats = [];
            $labels = [];
            $rowOffset = 0;

            foreach ((array) ($zone['rows'] ?? []) as $row) {
                foreach ((array) ($row['seats'] ?? []) as $col => $seat) {
                    $seats[] = [
                        'id' => $seat['id'] ?? sprintf('z%d-r%d-s%d', $zoneIndex, $rowOffset, $col),
                        'title' => $seat['title'] ?? sprintf('%s%d', strtoupper($row['name'] ?? 'A'), $col + 1),
                        'x' => (int) ($seat['x'] ?? ($col * 30)),
                        'y' => (int) ($seat['y'] ?? ($rowOffset * 30)),
                        'salable' => (bool) ($seat['salable'] ?? true),
                    ];
                }

                $labels[] = [
                    'title' => 'Row '.(string) ($row['name'] ?? chr(65 + $rowOffset)),
                    'x' => -30,
                    'y' => $rowOffset * 30,
                ];

                $rowOffset++;
            }

            $blocks[] = [
                'id' => $zone['id'] ?? 'zone-'.$zoneIndex,
                'title' => $zone['title'] ?? 'Zone '.($zoneIndex + 1),
                'color' => $zone['color'] ?? '#2c3e50',
                'seats' => $seats,
                'labels' => $labels,
            ];
        }

        return $blocks;
    }

    /**
     * @param  array<string, int>  $seatIdMap
     * @return array<int, array<string, mixed>>
     */
    private function relinkAssignments(SeatPlan $plan, array $seatIdMap): array
    {
        $orphans = [];

        /** @var iterable<int, SeatAssignment> $assignments */
        $assignments = $plan->seatAssignments()->get();

        foreach ($assignments as $assignment) {
            $legacySeatId = $this->legacySeatIdFor($assignment);

            if ($legacySeatId !== null && isset($seatIdMap[$legacySeatId])) {
                $assignment->forceFill(['seat_plan_seat_id' => $seatIdMap[$legacySeatId]])->save();

                continue;
            }

            $orphans[] = [
                'seat_plan_id' => $plan->id,
                'assignment_id' => $assignment->id,
                'ticket_id' => $assignment->ticket_id,
                'user_id' => $assignment->user_id,
                'legacy_seat_id' => $legacySeatId,
            ];
        }

        return $orphans;
    }

    private function legacySeatIdFor(SeatAssignment $assignment): ?string
    {
        $value = DB::table('seat_assignments')->where('id', $assignment->id)->value('seat_id');

        return is_string($value) || is_int($value) ? (string) $value : null;
    }
}
