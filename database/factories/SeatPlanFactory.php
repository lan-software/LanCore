<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatPlan>
 */
class SeatPlanFactory extends Factory
{
    protected $model = SeatPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'event_id' => Event::factory(),
            'data' => [
                'blocks' => [
                    [
                        'id' => 'block-1',
                        'title' => 'Main Hall',
                        'color' => fake()->hexColor(),
                        'seats' => [
                            [
                                'id' => 1,
                                'title' => 'A1',
                                'x' => 0,
                                'y' => 0,
                                'salable' => true,
                            ],
                            [
                                'id' => 2,
                                'title' => 'A2',
                                'x' => 30,
                                'y' => 0,
                                'salable' => true,
                            ],
                        ],
                        'labels' => [
                            [
                                'title' => 'Row A',
                                'x' => -30,
                                'y' => 0,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'data' => ['blocks' => []],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function withBlocks(array $blocks): static
    {
        return $this->state(fn (array $attributes): array => [
            'data' => ['blocks' => $blocks],
        ]);
    }
}
