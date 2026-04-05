<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-005
 */
class CreateTeam
{
    /**
     * @param  array{name: string, tag?: string|null}  $attributes
     */
    public function execute(Competition $competition, User $captain, array $attributes): CompetitionTeam
    {
        $team = CompetitionTeam::create([
            'competition_id' => $competition->id,
            'name' => $attributes['name'],
            'tag' => $attributes['tag'] ?? null,
            'captain_user_id' => $captain->id,
        ]);

        CompetitionTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $captain->id,
            'joined_at' => now(),
        ]);

        return $team->load('captain', 'activeMembers.user');
    }
}
