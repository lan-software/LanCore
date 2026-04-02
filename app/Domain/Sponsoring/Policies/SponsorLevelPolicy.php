<?php

namespace App\Domain\Sponsoring\Policies;

use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, SPO-F-004
 */
class SponsorLevelPolicy
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

    public function view(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->isAdmin();
    }
}
