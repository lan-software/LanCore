<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionTeam>
 */
class CompetitionTeamFactory extends Factory
{
    protected $model = CompetitionTeam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'name' => fake()->unique()->words(2, true),
            'tag' => fake()->lexify('???'),
            'captain_user_id' => User::factory(),
        ];
    }
}
