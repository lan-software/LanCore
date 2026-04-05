<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::ManageUsers);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::ManageUsers);
    }

    public function syncRoles(User $actor, User $user): bool
    {
        return $actor->hasPermission(Permission::SyncUserRoles);
    }

    public function updateAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission(Permission::DeleteUsers);
    }
}
