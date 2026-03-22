<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

class UpdateSeatPlan
{
    /**
     * @param  array{name?: string, data?: array<string, mixed>}  $attributes
     */
    public function execute(SeatPlan $seatPlan, array $attributes): void
    {
        $seatPlan->fill($attributes)->save();
    }
}
