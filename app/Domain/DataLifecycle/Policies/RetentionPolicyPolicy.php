<?php

namespace App\Domain\DataLifecycle\Policies;

use App\Domain\DataLifecycle\Enums\Permission;
use App\Models\User;

class RetentionPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRetentionPolicies);
    }

    public function view(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRetentionPolicies);
    }

    public function update(User $user): bool
    {
        return $user->hasPermission(Permission::ManageRetentionPolicies);
    }
}
