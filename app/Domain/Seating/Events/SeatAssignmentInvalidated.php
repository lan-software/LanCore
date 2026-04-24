<?php

namespace App\Domain\Seating\Events;

use App\Domain\Seating\Models\SeatPlan;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a confirmed invalidating seat-plan update has released an
 * assignment. The SeatAssignment row is already deleted at dispatch time so
 * we carry the snapshot data inline.
 *
 * @see docs/mil-std-498/SRS.md SET-F-013
 */
class SeatAssignmentInvalidated
{
    use Dispatchable;

    /**
     * @param  'seat_removed'|'category_mismatch'  $reason
     */
    public function __construct(
        public readonly int $ticketId,
        public readonly int $userId,
        public readonly SeatPlan $seatPlan,
        public readonly int $previousSeatId,
        public readonly ?string $previousSeatTitle,
        public readonly ?int $previousBlockId,
        public readonly string $reason,
    ) {}
}
