<?php

namespace Database\Factories;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMode>
 */
class GameModeFactory extends Factory
{
    protected $model = GameMode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'team_size' => fake()->randomElement([1, 2, 3, 5]),
            'parameters' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters): static
    {
        return $this->state(['parameters' => $parameters]);
    }
}
