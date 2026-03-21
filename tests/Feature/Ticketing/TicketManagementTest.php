<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows authenticated users to view their tickets', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    Ticket::factory()->create([
        'owner_id' => $user->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($user)
        ->get('/tickets')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('tickets/Index')
                ->has('ownedTickets', 1)
        );
});

it('allows ticket owner to view ticket detail', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $user->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($user)
        ->get("/tickets/{$ticket->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('tickets/Show')
                ->has('ticket')
                ->where('ticket.id', $ticket->id)
        );
});

it('prevents non-owners from viewing a ticket', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $other = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($other)
        ->get("/tickets/{$ticket->id}")
        ->assertForbidden();
});

it('allows owner to update ticket manager', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $manager = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($owner)
        ->patch("/tickets/{$ticket->id}/manager", [
            'manager_email' => $manager->email,
        ])
        ->assertRedirect();

    expect($ticket->fresh()->manager_id)->toBe($manager->id);
});

it('allows owner to update ticket user', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $ticketUser = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($owner)
        ->patch("/tickets/{$ticket->id}/user", [
            'user_email' => $ticketUser->email,
        ])
        ->assertRedirect();

    expect($ticket->fresh()->user_id)->toBe($ticketUser->id);
});

it('denies non-owners from updating ticket manager', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $other = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $this->actingAs($other)
        ->patch("/tickets/{$ticket->id}/manager", [
            'manager_id' => $other->id,
        ])
        ->assertForbidden();
});
