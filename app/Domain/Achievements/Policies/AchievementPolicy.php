<?php

namespace App\Domain\Achievements\Policies;

use App\Domain\Achievements\Models\Achievement;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, ACH-F-005
 */
class AchievementPolicy
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

    public function view(User $user, Achievement $achievement): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return $user->isAdmin();
    }
}
