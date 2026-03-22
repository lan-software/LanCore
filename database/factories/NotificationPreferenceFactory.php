<?php

namespace Database\Factories;

use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mail_on_news' => true,
            'mail_on_events' => true,
            'mail_on_news_comments' => true,
            'mail_on_program_time_slots' => true,
        ];
    }

    public function allDisabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mail_on_news' => false,
            'mail_on_events' => false,
            'mail_on_news_comments' => false,
            'mail_on_program_time_slots' => false,
        ]);
    }
}
