<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
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

    /**
     * Admins and superadmins can view the user listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins and superadmins can view a single user.
     */
    public function view(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Admins and superadmins can update users.
     */
    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Only superadmins can change roles on a user (handled via before()).
     */
    public function syncRoles(User $actor, User $user): bool
    {
        return false;
    }

    /**
     * Admins and superadmins can manage (bulk-update) users (handled via before() for superadmin).
     */
    public function updateAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only superadmins can bulk-delete users (handled via before()).
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
