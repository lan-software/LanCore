<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Support\SeatPlanTreeSyncer;
use App\Domain\Seating\Support\UpdateSeatPlanResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two-phase seat-plan update.
 *
 * Phase 1 (confirmInvalidations=false): diff the proposed block/row/seat
 * payload against the plan's current DB state, identify every existing
 * SeatAssignment the change would invalidate (seat removed OR the target
 * block's category allowlist would now reject the assignment's ticket-type),
 * and return without writing.
 *
 * Phase 2 (confirmInvalidations=true): persist the new tree AND delete the
 * invalidated assignments atomically, emitting a SeatAssignmentInvalidated
 * event per released assignment so listeners can notify affected parties.
 *
 * @see docs/mil-std-498/SSS.md CAP-SET-006
 * @see docs/mil-std-498/SRS.md SET-F-012, SET-F-013
 */
class UpdateSeatPlan
{
    public function __construct(
        private readonly SeatPlanTreeSyncer $syncer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        SeatPlan $seatPlan,
        array $attributes,
        bool $confirmInvalidations = false,
    ): UpdateSeatPlanResult {
        /** @var array<int, array<string, mixed>> $incomingBlocks */
        $incomingBlocks = array_values((array) ($attributes['data']['blocks'] ?? []));
        /** @var array<int, array<string, mixed>> $incomingPlanLabels */
        $incomingPlanLabels = array_values((array) ($attributes['data']['labels'] ?? []));

        $invalidations = $this->diffInvalidatedAssignments($seatPlan, $incomingBlocks);

        if ($invalidations->isNotEmpty() && ! $confirmInvalidations) {
            return UpdateSeatPlanResult::pending($invalidations);
        }

        return DB::transaction(function () use ($seatPlan, $attributes, $incomingBlocks, $incomingPlanLabels, $invalidations): UpdateSeatPlanResult {
            $this->persistPlanAttributes($seatPlan, $attributes);

            foreach ($invalidations as $invalidation) {
                /** @var SeatAssignment|null $assignment */
                $assignment = SeatAssignment::query()->find($invalidation['assignment_id']);

                if ($assignment === null) {
                    continue;
                }

                $ticketId = $assignment->ticket_id;
                $userId = $assignment->user_id;
                $previousSeatId = $assignment->seat_plan_seat_id;
                $previousSeatTitle = $invalidation['seat_title'] ?? null;
                $previousBlockId = $invalidation['block_id'] ?? null;

                $assignment->delete();

                SeatAssignmentInvalidated::dispatch(
                    $ticketId,
                    $userId,
                    $seatPlan,
                    (int) $previousSeatId,
                    is_string($previousSeatTitle) ? $previousSeatTitle : null,
                    is_numeric($previousBlockId) ? (int) $previousBlockId : null,
                    $invalidation['reason'],
                );
            }

            $idMap = $this->syncer->sync($seatPlan, $incomingBlocks, $incomingPlanLabels);

            return UpdateSeatPlanResult::saved($invalidations->count(), $idMap);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persistPlanAttributes(SeatPlan $seatPlan, array $attributes): void
    {
        $fields = array_intersect_key(
            $attributes,
            array_flip(['name', 'event_id', 'background_image_url'])
        );

        if ($fields === []) {
            return;
        }

        $seatPlan->fill($fields)->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $incomingBlocks
     * @return Collection<int, array<string, mixed>>
     */
    private function diffInvalidatedAssignments(SeatPlan $seatPlan, array $incomingBlocks): Collection
    {
        $incomingSeats = $this->indexIncomingSeats($incomingBlocks);
        $incomingSeatIds = array_keys($incomingSeats);

        /** @var Collection<int, SeatAssignment> $assignments */
        $assignments = $seatPlan->seatAssignments()
            ->with(['ticket.ticketType', 'user', 'seat.block'])
            ->get();

        return $assignments
            ->map(function (SeatAssignment $assignment) use ($incomingSeats, $incomingSeatIds): ?array {
                $seatId = $assignment->seat_plan_seat_id;

                if ($seatId === null || ! in_array($seatId, $incomingSeatIds, true)) {
                    return $this->buildRow($assignment, 'seat_removed');
                }

                $entry = $incomingSeats[$seatId];
                $categoryId = $assignment->ticket?->ticketType?->ticket_category_id;
                $allowed = $entry['allowed_category_ids'];

                if ($allowed !== [] && ! in_array($categoryId, $allowed, true)) {
                    return $this->buildRow($assignment, 'category_mismatch');
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Index the incoming payload by numeric seat PK so the invalidation diff
     * can detect seats that no longer appear. The editor emits seats flat at
     * the block level (with `row_id` references) — this method reads them
     * from that location.
     *
     * @param  array<int, array<string, mixed>>  $incomingBlocks
     * @return array<int, array{allowed_category_ids: array<int, int>}>
     */
    private function indexIncomingSeats(array $incomingBlocks): array
    {
        $index = [];

        foreach ($incomingBlocks as $block) {
            $allowed = $this->normaliseCategoryIds($block['allowed_ticket_category_ids'] ?? []);

            foreach ((array) ($block['seats'] ?? []) as $seat) {
                $rawId = $seat['id'] ?? null;

                if (! is_numeric($rawId)) {
                    continue;
                }

                $index[(int) $rawId] = ['allowed_category_ids' => $allowed];
            }
        }

        return $index;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, int>
     */
    private function normaliseCategoryIds(array $raw): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null,
            $raw,
        ), fn (?int $id): bool => $id !== null)));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(SeatAssignment $assignment, string $reason): array
    {
        $seat = $assignment->seat;
        $block = $seat?->block;
        $prefix = $block?->seat_title_prefix;
        $title = $seat?->title;

        return [
            'assignment_id' => $assignment->id,
            'ticket_id' => $assignment->ticket_id,
            'user_id' => $assignment->user_id,
            'seat_id' => $assignment->seat_plan_seat_id,
            'seat_title' => $title === null ? null : (is_string($prefix) ? $prefix : '').$title,
            'block_id' => $block?->id,
            'block_title' => $block?->title,
            'assignee_name' => $assignment->user?->name,
            'reason' => $reason,
        ];
    }
}
