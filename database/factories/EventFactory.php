<?php

namespace Database\Factories;

use App\Domain\Event\Enums\AttendanceMode;
use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Enums\EventSyndicationStatus;
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
            'banner_images' => [],
            'status' => EventStatus::Draft,
            'venue_id' => Venue::factory(),
            'attendance_mode' => AttendanceMode::Offline,
            'syndication_status' => EventSyndicationStatus::Scheduled,
            'previous_start_date' => null,
            'has_showers' => fake()->boolean(),
            'sleeping' => 0,
            'alcohol_policy' => 0,
            'smoking_policy' => 0,
            'age_policy' => 0,
            'food_policy' => 0,
            'network_connection_mbps' => null,
            'internet_connection_mbps' => null,
            'wifi_connection_mbps' => null,
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
