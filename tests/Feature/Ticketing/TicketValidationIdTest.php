<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

test('ticket generates validation_id on creation', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    $ticket = Ticket::create([
        'status' => TicketStatus::Active,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
        'owner_id' => $user->id,
        'manager_id' => $user->id,
        'user_id' => $user->id,
    ]);

    expect($ticket->validation_id)->toBeString();
    expect($ticket->validation_id)->toHaveLength(16);
    expect($ticket->validation_id)->toMatch('/^[A-Z0-9]+$/');
});

test('ticket validation_id is unique across tickets', function () {
    $tickets = Ticket::factory()->count(5)->create();

    $ids = $tickets->pluck('validation_id')->toArray();

    expect(array_unique($ids))->toHaveCount(count($ids));
});

test('ticket factory generates validation_id', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->validation_id)->toBeString();
    expect($ticket->validation_id)->toHaveLength(16);
});
