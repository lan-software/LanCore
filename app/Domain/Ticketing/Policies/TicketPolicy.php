<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Enums\Permission;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md TKT-F-008
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::ManageTicketing)
            || $ticket->owner_id === $user->id
            || $ticket->manager_id === $user->id
            || $ticket->users->contains('id', $user->id);
    }

    public function updateManager(User $user, Ticket $ticket): bool
    {
        if ($ticket->checked_in_at !== null) {
            return false;
        }

        return $user->hasPermission(Permission::ManageTicketing) || $ticket->owner_id === $user->id;
    }

    public function updateUser(User $user, Ticket $ticket): bool
    {
        if ($ticket->checked_in_at !== null) {
            return false;
        }

        return $user->hasPermission(Permission::ManageTicketing)
            || $ticket->owner_id === $user->id
            || $ticket->manager_id === $user->id;
    }

    public function checkIn(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::CheckInTickets);
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::ManageTicketing) || $ticket->owner_id === $user->id;
    }

    /**
     * Authorize picking/changing the seat for a specific assignee on a ticket.
     *
     * Owners, managers and ManageTicketing permission holders may seat anyone on the ticket.
     * Other users may only seat themselves AND only if they actually appear on the ticket's
     * user pivot. Roles are non-exclusive — being a user on the ticket never blocks owner
     * or manager rights.
     *
     * @see docs/mil-std-498/SRS.md SET-F-007
     */
    public function pickSeat(User $user, Ticket $ticket, User $assignee): bool
    {
        if ($ticket->checked_in_at !== null) {
            return false;
        }

        if ($user->hasPermission(Permission::ManageTicketing)) {
            return true;
        }

        if ($ticket->owner_id === $user->id || $ticket->manager_id === $user->id) {
            return true;
        }

        return $user->id === $assignee->id
            && $ticket->users->contains('id', $user->id);
    }
}
