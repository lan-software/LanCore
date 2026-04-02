<?php

namespace App\Domain\Integration\Policies;

use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, INT-F-009
 */
class IntegrationAppPolicy
{
    /**
     * Superadmin bypasses all authorization checks.
     */
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

    public function view(User $user, IntegrationApp $app): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, IntegrationApp $app): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, IntegrationApp $app): bool
    {
        return $user->isAdmin();
    }

    public function manageTokens(User $user, IntegrationApp $app): bool
    {
        return $user->isAdmin();
    }
}
