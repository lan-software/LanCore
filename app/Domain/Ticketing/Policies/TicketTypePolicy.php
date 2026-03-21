<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

class TicketTypePolicy
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

    public function view(User $user, TicketType $ticketType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        return $user->isAdmin();
    }
}
