<?php

namespace App\Domain\Seating\Actions;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

/**
 * Release a user's seat on a ticket. Idempotent — silently no-ops if no assignment exists.
 *
 * @see docs/mil-std-498/SRS.md SET-F-008
 */
class ReleaseSeat
{
    public function execute(Ticket $ticket, User $assignee): void
    {
        SeatAssignment::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $assignee->id)
            ->delete();
    }
}
