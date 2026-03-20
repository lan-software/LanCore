<?php

namespace Database\Factories;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'name' => fake()->words(3, true).' LAN',
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, (clone $startDate)->modify('+3 days')),
            'banner_image' => null,
            'status' => EventStatus::Draft,
            'venue_id' => Venue::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Published,
        ]);
    }

    public function withoutVenue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'venue_id' => null,
        ]);
    }
}
