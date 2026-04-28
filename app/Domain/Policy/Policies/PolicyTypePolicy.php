<?php

namespace App\Domain\Policy\Policies;

use App\Domain\Policy\Enums\Permission;
use App\Domain\Policy\Models\PolicyType;
use App\Models\User;

class PolicyTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function view(User $user, PolicyType $policyType): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function update(User $user, PolicyType $policyType): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function delete(User $user, PolicyType $policyType): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }
}
