<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanSeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlanSeat>
 */
class SeatPlanSeatFactory extends Factory
{
    protected $model = SeatPlanSeat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $block = SeatPlanBlock::factory()->create();

        return [
            'seat_plan_id' => $block->seat_plan_id,
            'seat_plan_block_id' => $block->id,
            'seat_plan_row_id' => null,
            'number' => fake()->unique()->numberBetween(1, 999),
            'title' => 'A'.fake()->unique()->numberBetween(1, 999),
            'x' => fake()->numberBetween(0, 500),
            'y' => fake()->numberBetween(0, 500),
            'salable' => true,
            'color' => null,
            'note' => null,
            'custom_data' => null,
        ];
    }
}
