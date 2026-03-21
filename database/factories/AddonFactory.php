<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Addon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Addon>
 */
class AddonFactory extends Factory
{
    protected $model = Addon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['2.5 Gbit Ethernet', '10 Gbit Ethernet', 'Premium Chair', 'Coffee Flatrate', 'Extra Table']),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(500, 5000),
            'quota' => fake()->optional()->numberBetween(10, 100),
            'seats_consumed' => 0,
            'requires_ticket' => true,
            'is_hidden' => false,
            'event_id' => Event::factory(),
        ];
    }

    public function consumesSeat(int $seats = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'seats_consumed' => $seats,
        ]);
    }

    public function standaloneAddon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_ticket' => false,
        ]);
    }
}
