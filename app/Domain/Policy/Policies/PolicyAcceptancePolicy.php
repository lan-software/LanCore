<?php

namespace App\Domain\Policy\Policies;

use App\Domain\Policy\Enums\Permission;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Models\User;

class PolicyAcceptancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePolicies);
    }

    public function view(User $user, PolicyAcceptance $acceptance): bool
    {
        return $user->id === $acceptance->user_id
            || $user->hasPermission(Permission::ManagePolicies);
    }

    public function withdraw(User $user, PolicyAcceptance $acceptance): bool
    {
        return $user->id === $acceptance->user_id;
    }
}
