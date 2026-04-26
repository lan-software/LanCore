<?php

namespace Database\Factories;

use App\Domain\Achievements\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'notification_text' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['trophy', 'star', 'medal', 'award', 'flame', 'zap', 'shield', 'crown']),
            'is_active' => true,
            'earned_user_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
