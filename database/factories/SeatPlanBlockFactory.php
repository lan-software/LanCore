<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlanBlock>
 */
class SeatPlanBlockFactory extends Factory
{
    protected $model = SeatPlanBlock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seat_plan_id' => SeatPlan::factory()->empty(),
            'title' => fake()->words(2, true),
            'color' => fake()->hexColor(),
            'seat_title_prefix' => null,
            'background_image_url' => null,
            'sort_order' => 0,
        ];
    }

    public function withPrefix(string $prefix): static
    {
        return $this->state(fn (array $attributes): array => [
            'seat_title_prefix' => $prefix,
        ]);
    }
}
