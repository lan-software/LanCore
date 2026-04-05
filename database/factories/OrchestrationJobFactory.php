<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Competition;
use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrchestrationJob>
 */
class OrchestrationJobFactory extends Factory
{
    protected $model = OrchestrationJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'lanbrackets_match_id' => fake()->unique()->numberBetween(1, 10000),
            'game_id' => Game::factory(),
            'status' => OrchestrationJobStatus::Pending,
            'match_config' => [
                'map_pool' => ['de_dust2', 'de_mirage', 'de_inferno'],
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => OrchestrationJobStatus::Pending]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => OrchestrationJobStatus::Active,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => OrchestrationJobStatus::Completed,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => OrchestrationJobStatus::Failed,
            'error_message' => 'No available game server',
        ]);
    }
}
