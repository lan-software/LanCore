<?php

namespace App\Console\Commands\Ticketing;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ticketing:tickets:list {--event= : Filter by event ID} {--status= : Filter by status (active, checked_in, cancelled)} {--owner= : Filter by owner user ID}')]
#[Description('List all tickets')]
class ListTickets extends Command
{
    public function handle(): int
    {
        $query = Ticket::query()->with(['ticketType', 'event', 'owner']);

        if ($this->option('status') !== null) {
            $status = TicketStatus::tryFrom($this->option('status'));

            if (! $status) {
                $this->error("Invalid status '{$this->option('status')}'. Valid statuses: ".implode(', ', array_column(TicketStatus::cases(), 'value')));

                return self::FAILURE;
            }

            $query->where('status', $status);
        }

        if ($this->option('event') !== null) {
            $query->where('event_id', $this->option('event'));
        }

        if ($this->option('owner') !== null) {
            $query->where('owner_id', $this->option('owner'));
        }

        $tickets = $query->orderBy('id')->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Status', 'Type', 'Event', 'Owner', 'Checked In'],
            $tickets->map(fn (Ticket $ticket) => [
                $ticket->id,
                $ticket->status->value,
                $ticket->ticketType?->name ?? '-',
                $ticket->event?->name ?? '-',
                $ticket->owner?->name ?? '-',
                $ticket->checked_in_at?->format('Y-m-d H:i') ?? '-',
            ]),
        );

        return self::SUCCESS;
    }
}
