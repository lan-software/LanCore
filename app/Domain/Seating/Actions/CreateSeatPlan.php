<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatPlan;

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
