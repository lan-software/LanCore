<?php

namespace App\Domain\Competition\Policies;

use App\Domain\Competition\Enums\Permission;
use App\Domain\Competition\Models\Competition;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-002
 */
class CompetitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Competition $competition): bool
    {
        if ($user->hasPermission(Permission::ManageCompetitions)) {
            return true;
        }

        return $competition->teams()
            ->whereHas('activeMembers', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->hasPermission(Permission::ManageCompetitions);
    }
}
