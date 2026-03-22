<?php

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;

it('lists all tickets in a table', function () {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);

    $this->artisan('ticketing:tickets:list')
        ->expectsTable(
            ['ID', 'Status', 'Type', 'Event', 'Owner', 'Checked In'],
            [
                [
                    $ticket->id,
                    'active',
                    $ticket->ticketType?->name ?? '-',
                    $ticket->event?->name ?? '-',
                    $ticket->owner?->name ?? '-',
                    '-',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters tickets by status', function () {
    Ticket::factory()->create(['status' => TicketStatus::Cancelled]);
    $active = Ticket::factory()->create(['status' => TicketStatus::Active]);

    $this->artisan('ticketing:tickets:list --status=active')
        ->expectsOutputToContain($active->ticketType?->name ?? '-')
        ->assertSuccessful();
});

it('shows error for invalid status', function () {
    $this->artisan('ticketing:tickets:list --status=invalid')
        ->expectsOutputToContain("Invalid status 'invalid'")
        ->assertFailed();
});

it('filters tickets by event', function () {
    $ticket = Ticket::factory()->create();
    Ticket::factory()->create();

    $this->artisan("ticketing:tickets:list --event={$ticket->event_id}")
        ->expectsOutputToContain($ticket->event?->name ?? '-')
        ->assertSuccessful();
});

it('filters tickets by owner', function () {
    $ticket = Ticket::factory()->create();
    Ticket::factory()->create();

    $this->artisan("ticketing:tickets:list --owner={$ticket->owner_id}")
        ->assertSuccessful();
});

it('shows message when no tickets found', function () {
    $this->artisan('ticketing:tickets:list')
        ->expectsOutputToContain('No tickets found.')
        ->assertSuccessful();
});
