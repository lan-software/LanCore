<?php

namespace Database\Factories;

use App\Domain\Orchestration\Models\MatchChatMessage;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchChatMessage>
 */
class MatchChatMessageFactory extends Factory
{
    protected $model = MatchChatMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orchestration_job_id' => OrchestrationJob::factory(),
            'steam_id' => (string) fake()->numberBetween(76561190000000000, 76561199999999999),
            'player_name' => fake()->userName(),
            'message' => fake()->sentence(),
            'is_team_chat' => fake()->boolean(30),
            'timestamp' => now(),
            'created_at' => now(),
        ];
    }

    public function teamChat(): static
    {
        return $this->state(['is_team_chat' => true]);
    }
}
