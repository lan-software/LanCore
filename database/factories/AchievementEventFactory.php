<?php

namespace Database\Factories;

use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Achievements\Models\AchievementEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementEvent>
 */
class AchievementEventFactory extends Factory
{
    protected $model = AchievementEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'achievement_id' => Achievement::factory(),
            'event_class' => fake()->randomElement(array_column(GrantableEvent::cases(), 'value')),
        ];
    }
}
