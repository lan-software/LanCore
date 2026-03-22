<?php

namespace App\Domain\Sponsoring\Policies;

use App\Domain\Sponsoring\Models\Sponsor;
use App\Models\User;

class SponsorPolicy
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
        return $user->isAdmin() || $user->isSponsorManager();
    }

    public function view(User $user, Sponsor $sponsor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->managedSponsors()->where('sponsor_id', $sponsor->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->managedSponsors()->where('sponsor_id', $sponsor->id)->exists();
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $user->isAdmin();
    }

    public function manageEvents(User $user, Sponsor $sponsor): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, Sponsor $sponsor): bool
    {
        return $user->isAdmin();
    }
}
