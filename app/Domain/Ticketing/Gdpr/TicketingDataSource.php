<?php

namespace App\Domain\Ticketing\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

class TicketingDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'ticketing';
    }

    public function label(): string
    {
        return 'Tickets and seat assignments';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $tickets = Ticket::query()
            ->where(function ($q) use ($user): void {
                $q->where('owner_id', $user->id)
                    ->orWhere('manager_id', $user->id)
                    ->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id));
            })
            ->with(['users:id', 'seatAssignments'])
            ->orderBy('id')
            ->get()
            ->map(function (Ticket $ticket) use ($user, $context): array {
                $row = $ticket->attributesToArray();
                $row['owner'] = $ticket->owner_id === $user->id ? 'subject'
                    : ($ticket->owner_id ? $context->obfuscateUser($ticket->owner_id, 'ticket owner') : null);
                $row['manager'] = $ticket->manager_id === $user->id ? 'subject'
                    : ($ticket->manager_id ? $context->obfuscateUser($ticket->manager_id, 'ticket manager') : null);

                unset($row['owner_id'], $row['manager_id']);

                $row['ticket_users'] = $ticket->users->map(
                    fn ($u) => $u->id === $user->id ? 'subject' : $context->obfuscateUser($u->id, 'ticket co-user'),
                )->all();

                return $row;
            })
            ->all();

        $seatAssignments = SeatAssignment::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        return new GdprDataSourceResult([
            'tickets' => $tickets,
            'seat_assignments' => $seatAssignments,
        ]);
    }
}
