<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-006
 */
class JoinTeam
{
    public function execute(CompetitionTeam $team, User $user): CompetitionTeamMember
    {
        return CompetitionTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);
    }
}
