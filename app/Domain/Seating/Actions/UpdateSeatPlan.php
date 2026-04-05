<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

/**
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-002
 */
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
