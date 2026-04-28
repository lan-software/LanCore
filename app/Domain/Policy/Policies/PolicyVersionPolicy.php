<?php

namespace App\Domain\Policy\Policies;

use App\Domain\Policy\Enums\Permission;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;

class PolicyVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function view(User $user, PolicyVersion $version): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function update(User $user, PolicyVersion $version): bool
    {
        return false;
    }

    public function delete(User $user, PolicyVersion $version): bool
    {
        return false;
    }
}
