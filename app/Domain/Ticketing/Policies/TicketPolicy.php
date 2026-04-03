<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md TKT-F-008
 */
class TicketPolicy
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

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->owner_id === $user->id
            || $ticket->manager_id === $user->id
            || $ticket->users->contains('id', $user->id);
    }

    public function updateManager(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->owner_id === $user->id;
    }

    public function updateUser(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->owner_id === $user->id
            || $ticket->manager_id === $user->id;
    }

    public function checkIn(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->owner_id === $user->id;
    }
}
