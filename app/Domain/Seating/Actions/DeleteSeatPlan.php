<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

/**
 * @see docs/mil-std-498/SRS.md SET-F-001
 */
class DeleteSeatPlan
{
    public function execute(SeatPlan $seatPlan): void
    {
        $seatPlan->delete();
    }
}
