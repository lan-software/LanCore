<?php

namespace App\Domain\OrgaTeam\Policies;

use App\Domain\OrgaTeam\Enums\Permission;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md OT-F-006, OT-F-010
 */
class OrgaTeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function view(User $user, OrgaTeam $orgaTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function update(User $user, OrgaTeam $orgaTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function delete(User $user, OrgaTeam $orgaTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function assignToEvent(User $user): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }
}
