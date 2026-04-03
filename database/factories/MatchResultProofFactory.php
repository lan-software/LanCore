<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\MatchResultProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchResultProof>
 */
class MatchResultProofFactory extends Factory
{
    protected $model = MatchResultProof::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'lanbrackets_match_id' => fake()->numberBetween(1, 1000),
            'submitted_by_user_id' => User::factory(),
            'submitted_by_team_id' => CompetitionTeam::factory(),
            'screenshot_path' => 'proofs/'.fake()->uuid().'.png',
            'scores' => [
                ['participant_id' => 1, 'score' => fake()->numberBetween(0, 16)],
                ['participant_id' => 2, 'score' => fake()->numberBetween(0, 16)],
            ],
            'is_disputed' => false,
        ];
    }

    public function disputed(): static
    {
        return $this->state(['is_disputed' => true]);
    }

    public function resolved(): static
    {
        return $this->state(['resolved_at' => now()]);
    }
}
