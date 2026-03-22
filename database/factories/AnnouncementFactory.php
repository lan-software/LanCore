<?php

namespace Database\Factories;

use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => AnnouncementPriority::Normal,
            'event_id' => Event::factory(),
            'author_id' => User::factory(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => now(),
        ]);
    }

    public function silent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => AnnouncementPriority::Silent,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => AnnouncementPriority::Emergency,
        ]);
    }
}
