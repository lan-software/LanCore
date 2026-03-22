<?php

namespace App\Domain\Sponsoring\Policies;

use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Models\User;

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
