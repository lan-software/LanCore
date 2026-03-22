<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

class DeleteSeatPlan
{
    public function execute(SeatPlan $seatPlan): void
    {
        $seatPlan->delete();
    }
}
