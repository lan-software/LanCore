<?php

namespace App\Domain\Shop\Policies;

use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md SHP-F-014
 */
class PaymentProviderConditionPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
