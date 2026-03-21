<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTicketAssignments
{
    public function updateManager(Ticket $ticket, ?User $manager, int $performedBy): Ticket
    {
        return DB::transaction(function () use ($ticket, $manager): Ticket {
            $ticket->update(['manager_id' => $manager?->id]);

            return $ticket;
        });
    }

    public function updateUser(Ticket $ticket, ?User $ticketUser, int $performedBy): Ticket
    {
        return DB::transaction(function () use ($ticket, $ticketUser): Ticket {
            $ticket->update(['user_id' => $ticketUser?->id]);

            return $ticket;
        });
    }

    public function checkIn(Ticket $ticket, int $performedBy): Ticket
    {
        return DB::transaction(function () use ($ticket): Ticket {
            $ticket->update([
                'status' => TicketStatus::CheckedIn,
                'checked_in_at' => now(),
            ]);

            return $ticket;
        });
    }
}
