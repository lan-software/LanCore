<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

/**
 * @see docs/mil-std-498/SSS.md CAP-SET-001
 * @see docs/mil-std-498/SRS.md SET-F-001, SET-F-002
 */
class CreateSeatPlan
{
    /**
     * @param  array{name: string, event_id: int, data?: array<string, mixed>}  $attributes
     */
    public function execute(array $attributes): SeatPlan
    {
        $attributes['data'] ??= ['blocks' => []];

        return SeatPlan::create($attributes);
    }
}
