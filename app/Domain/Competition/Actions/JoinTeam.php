<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\Competition\SignupRules\Support\SignupRuleEnforcer;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-006, COMP-F-014
 */
class JoinTeam
{
    public function __construct(private readonly SignupRuleEnforcer $signupRules) {}

    public function execute(CompetitionTeam $team, User $user): CompetitionTeamMember
    {
        $this->signupRules->enforce($user, $team->competition);

        return CompetitionTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);
    }
}
