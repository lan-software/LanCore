<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlanRow>
 */
class SeatPlanRowFactory extends Factory
{
    protected $model = SeatPlanRow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seat_plan_block_id' => SeatPlanBlock::factory(),
            'name' => strtoupper(fake()->lexify('?')),
            'sort_order' => 0,
        ];
    }
}
