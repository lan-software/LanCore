<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-007
 */
class LeaveTeam
{
    public function execute(CompetitionTeam $team, User $user): bool
    {
        $team->activeMembers()
            ->where('user_id', $user->id)
            ->update(['left_at' => now()]);

        if ($team->captain_user_id === $user->id) {
            $nextCaptain = $team->activeMembers()
                ->orderBy('joined_at')
                ->first();

            if ($nextCaptain) {
                $team->update(['captain_user_id' => $nextCaptain->user_id]);
            } else {
                $team->delete();

                return true;
            }
        }

        return false;
    }
}
