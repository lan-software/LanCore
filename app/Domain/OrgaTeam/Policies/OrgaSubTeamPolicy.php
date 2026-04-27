<?php

namespace App\Domain\OrgaTeam\Policies;

use App\Domain\OrgaTeam\Enums\Permission;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md OT-F-006
 */
class OrgaSubTeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function view(User $user, OrgaSubTeam $orgaSubTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function update(User $user, OrgaSubTeam $orgaSubTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }

    public function delete(User $user, OrgaSubTeam $orgaSubTeam): bool
    {
        return $user->hasPermission(Permission::ManageOrgaTeams);
    }
}
