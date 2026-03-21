<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Premium', 'Standard', 'Clan', 'VIP', 'Economy']),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
            'event_id' => Event::factory(),
        ];
    }
}
