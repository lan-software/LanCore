<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Models\TicketCategory;
use App\Models\User;

class TicketCategoryPolicy
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

    public function view(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->isAdmin();
    }
}
