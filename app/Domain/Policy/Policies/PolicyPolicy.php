<?php

namespace App\Domain\Policy\Policies;

use App\Domain\Policy\Enums\Permission;
use App\Domain\Policy\Models\Policy;
use App\Models\User;

class PolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function view(User $user, Policy $policy): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function update(User $user, Policy $policy): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }
}
