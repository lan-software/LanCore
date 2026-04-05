<?php

namespace App\Domain\Shop\Policies;

use App\Domain\Shop\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md SHP-F-014
 */
class PaymentProviderConditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageShopConditions);
    }

    public function view(User $user): bool
    {
        return $user->hasPermission(Permission::ManageShopConditions);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageShopConditions);
    }

    public function update(User $user): bool
    {
        return $user->hasPermission(Permission::ManageShopConditions);
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission(Permission::ManageShopConditions);
    }
}
