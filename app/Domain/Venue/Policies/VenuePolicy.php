<?php

namespace App\Domain\Venue\Policies;

use App\Domain\Venue\Models\Venue;
use App\Models\User;

class VenuePolicy
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

    public function view(User $user, Venue $venue): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->isAdmin();
    }
}
