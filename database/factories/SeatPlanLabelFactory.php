<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlanLabel>
 */
class SeatPlanLabelFactory extends Factory
{
    protected $model = SeatPlanLabel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $block = SeatPlanBlock::factory()->create();

        return [
            'seat_plan_id' => $block->seat_plan_id,
            'seat_plan_block_id' => $block->id,
            'title' => fake()->words(2, true),
            'x' => fake()->numberBetween(-100, 100),
            'y' => fake()->numberBetween(-100, 100),
            'sort_order' => 0,
        ];
    }

    /**
     * Plan-level (un-blocked) label.
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes): array => [
            'seat_plan_block_id' => null,
        ]);
    }
}
