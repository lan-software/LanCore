<?php

namespace App\Domain\Competition\Policies;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\Permission;
use App\Domain\Competition\Models\Competition;
use App\Models\User;

class MatchResultProofPolicy
{
    public function create(User $user, Competition $competition): bool
    {
        if ($competition->status !== CompetitionStatus::Running) {
            return false;
        }

        if ($user->hasPermission(Permission::ManageCompetitions)) {
            return true;
        }

        if (! $competition->allowsParticipantResults()) {
            return false;
        }

        return $competition->teams()
            ->whereHas('activeMembers', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }
}
