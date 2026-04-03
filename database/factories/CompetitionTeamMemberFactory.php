<?php

namespace Database\Factories;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionTeamMember>
 */
class CompetitionTeamMemberFactory extends Factory
{
    protected $model = CompetitionTeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => CompetitionTeam::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
        ];
    }
}
