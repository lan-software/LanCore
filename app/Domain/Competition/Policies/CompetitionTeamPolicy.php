<?php

namespace App\Domain\Competition\Policies;

use App\Domain\Competition\Enums\Permission;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Models\User;

class CompetitionTeamPolicy
{
    public function create(User $user, Competition $competition): bool
    {
        if (! $competition->isRegistrationOpen()) {
            return false;
        }

        return ! $competition->teams()
            ->whereHas('activeMembers', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function join(User $user, CompetitionTeam $team): bool
    {
        if (! $team->competition->isRegistrationOpen()) {
            return false;
        }

        if ($team->isFull()) {
            return false;
        }

        return ! $team->hasMember($user);
    }

    public function leave(User $user, CompetitionTeam $team): bool
    {
        return $team->hasMember($user);
    }

    public function manage(User $user, CompetitionTeam $team): bool
    {
        if ($user->hasPermission(Permission::ManageCompetitions)) {
            return true;
        }

        return $team->captain_user_id === $user->id;
    }
}
