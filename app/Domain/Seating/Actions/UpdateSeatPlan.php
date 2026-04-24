<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Support\SeatingCategoryRules;
use App\Domain\Seating\Support\UpdateSeatPlanResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Two-phase seat-plan update.
 *
 * Phase 1 (confirmInvalidations=false): diff old vs new JSON, identify every
 * existing SeatAssignment the change would invalidate (seat removed OR the
 * target block's category allowlist would now reject the assignment's
 * ticket-type), and return without writing.
 *
 * Phase 2 (confirmInvalidations=true): persist the new data AND delete the
 * invalidated assignments atomically, emitting a SeatAssignmentInvalidated
 * event per released assignment so listeners can notify affected parties.
 *
 * @see docs/mil-std-498/SSS.md CAP-SET-006
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-002, SET-F-012, SET-F-013
 */
class UpdateSeatPlan
{
    /**
     * @param  array{name?: string, data?: array<string, mixed>}  $attributes
     */
    public function execute(
        SeatPlan $seatPlan,
        array $attributes,
        bool $confirmInvalidations = false,
    ): UpdateSeatPlanResult {
        /** @var array<string, mixed> $newData */
        $newData = $attributes['data'] ?? $seatPlan->data ?? ['blocks' => []];

        $invalidations = $this->diffInvalidatedAssignments($seatPlan, $newData);

        if ($invalidations->isNotEmpty() && ! $confirmInvalidations) {
            return UpdateSeatPlanResult::pending($invalidations);
        }

        // Fast path: nothing to release, skip the transaction entirely.
        if ($invalidations->isEmpty()) {
            $seatPlan->fill($attributes)->save();

            return UpdateSeatPlanResult::saved(0);
        }

        return DB::transaction(function () use ($seatPlan, $attributes, $invalidations): UpdateSeatPlanResult {
            $seatPlan->fill($attributes)->save();
            $seatPlan->refresh();

            foreach ($invalidations as $invalidation) {
                /** @var SeatAssignment|null $assignment */
                $assignment = SeatAssignment::query()->find($invalidation['assignment_id']);

                if ($assignment === null) {
                    continue;
                }

                $ticketId = $assignment->ticket_id;
                $userId = $assignment->user_id;
                $previousSeatId = $assignment->seat_id;

                $assignment->delete();

                SeatAssignmentInvalidated::dispatch(
                    $ticketId,
                    $userId,
                    $seatPlan,
                    $previousSeatId,
                    $invalidation['block_id'] ?? null,
                    $invalidation['reason'],
                );
            }

            return UpdateSeatPlanResult::saved($invalidations->count());
        });
    }

    /**
     * Build the invalidation rows (one per existing SeatAssignment that the
     * proposed new JSON would orphan). Returns both the reason and enough
     * metadata for the confirmation dialog to render (assignee name + seat
     * label).
     *
     * @param  array<string, mixed>  $newData
     * @return Collection<int, array<string, mixed>>
     */
    private function diffInvalidatedAssignments(SeatPlan $seatPlan, array $newData): Collection
    {
        /** @var Collection<int, SeatAssignment> $assignments */
        $assignments = $seatPlan->seatAssignments()
            ->with(['ticket.ticketType', 'user'])
            ->get();

        $newBlocks = $this->indexBlocksBySeat($newData);

        return $assignments
            ->map(function (SeatAssignment $assignment) use ($newBlocks): ?array {
                $entry = $newBlocks[$assignment->seat_id] ?? null;

                if ($entry === null) {
                    return $this->buildRow($assignment, null, 'seat_removed');
                }

                $categoryId = $assignment->ticket?->ticketType?->ticket_category_id;
                $allowed = SeatingCategoryRules::allowedCategoryIds($entry['block']);

                if ($allowed !== [] && ! in_array($categoryId, $allowed, true)) {
                    return $this->buildRow($assignment, $entry['block'], 'category_mismatch');
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $newData
     * @return array<string, array{block: array<string, mixed>, seat: array<string, mixed>}>
     */
    private function indexBlocksBySeat(array $newData): array
    {
        $index = [];

        foreach (($newData['blocks'] ?? []) as $block) {
            foreach (($block['seats'] ?? []) as $seat) {
                $seatId = (string) ($seat['id'] ?? '');
                if ($seatId === '') {
                    continue;
                }
                $index[$seatId] = ['block' => $block, 'seat' => $seat];
            }
        }

        return $index;
    }

    /**
     * @param  array<string, mixed>|null  $block
     * @return array<string, mixed>
     */
    private function buildRow(SeatAssignment $assignment, ?array $block, string $reason): array
    {
        return [
            'assignment_id' => $assignment->id,
            'ticket_id' => $assignment->ticket_id,
            'user_id' => $assignment->user_id,
            'seat_id' => $assignment->seat_id,
            'seat_title' => $assignment->seat_title,
            'block_id' => $block === null ? null : (string) ($block['id'] ?? ''),
            'block_title' => $block === null ? null : (string) ($block['title'] ?? ''),
            'assignee_name' => $assignment->user?->name,
            'reason' => $reason,
        ];
    }
}
