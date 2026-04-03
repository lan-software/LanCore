<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Enums\CheckInMode;
use App\Domain\Ticketing\Enums\TicketStatus;
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

// ---- Ticket Type Group Fields ----

it('creates a group ticket type with max_users_per_ticket and check_in_mode', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/ticket-types', [
            'name' => 'Team Ticket',
            'price' => 8000,
            'quota' => 50,
            'seats_per_ticket' => 2,
            'max_users_per_ticket' => 4,
            'check_in_mode' => 'group',
            'is_seatable' => true,
            'is_hidden' => false,
            'event_id' => $event->id,
        ])
        ->assertRedirect('/ticket-types');

    $ticketType = TicketType::where('name', 'Team Ticket')->first();
    expect($ticketType)->not->toBeNull();
    expect($ticketType->max_users_per_ticket)->toBe(4);
    expect($ticketType->check_in_mode)->toBe(CheckInMode::Group);
    expect($ticketType->totalSeatsConsumed())->toBe(8);
});

it('defaults max_users_per_ticket to 1', function () {
    $ticketType = TicketType::factory()->create();

    expect($ticketType->max_users_per_ticket)->toBe(1);
    expect($ticketType->check_in_mode)->toBe(CheckInMode::Individual);
    expect($ticketType->isGroupTicket())->toBeFalse();
});

it('rejects max_users_per_ticket below 1', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/ticket-types', [
            'name' => 'Invalid',
            'price' => 1000,
            'quota' => 10,
            'seats_per_ticket' => 1,
            'max_users_per_ticket' => 0,
            'event_id' => $event->id,
        ])
        ->assertSessionHasErrors('max_users_per_ticket');
});

it('prevents changing group fields on locked ticket types', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->locked()->groupTicket(4, 'individual')->create();

    $this->actingAs($admin)
        ->patch("/ticket-types/{$ticketType->id}", [
            'name' => 'Changed',
            'max_users_per_ticket' => 2,
            'check_in_mode' => 'group',
        ])
        ->assertRedirect();

    $updated = $ticketType->fresh();
    expect($updated->name)->toBe('Changed');
    expect($updated->max_users_per_ticket)->toBe(4); // Locked, unchanged
    expect($updated->check_in_mode)->toBe(CheckInMode::Individual); // Locked, unchanged
});

// ---- User Assignment ----

it('assigns multiple users to a group ticket up to the limit', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $users = User::factory()->withRole(RoleName::User)->count(2)->create();

    foreach ($users as $user) {
        $this->actingAs($owner)
            ->post("/tickets/{$ticket->id}/users", [
                'user_email' => $user->email,
            ])
            ->assertRedirect();
    }

    expect($ticket->fresh()->users)->toHaveCount(2);
});

it('rejects exceeding max_users_per_ticket', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(1)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    // First user should succeed
    $user1 = User::factory()->withRole(RoleName::User)->create();
    $ticket->users()->attach($user1->id);

    // Second user should fail (max_users_per_ticket = 1)
    $user2 = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($owner)
        ->post("/tickets/{$ticket->id}/users", [
            'user_email' => $user2->email,
        ])
        ->assertStatus(500); // InvalidArgumentException

    expect($ticket->fresh()->users)->toHaveCount(1);
});

it('removes an assigned user from a group ticket', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();
    $ticket->users()->attach($user->id);

    $this->actingAs($owner)
        ->delete("/tickets/{$ticket->id}/users/{$user->id}")
        ->assertRedirect();

    expect($ticket->fresh()->users)->toHaveCount(0);
});

// ---- Seat Capacity ----

it('calculates seat consumption as seats_per_ticket times max_users_per_ticket', function () {
    $ticketType = TicketType::factory()->create([
        'seats_per_ticket' => 2,
        'max_users_per_ticket' => 4,
    ]);

    expect($ticketType->totalSeatsConsumed())->toBe(8);
    expect($ticketType->isGroupTicket())->toBeTrue();
});

it('validates seat capacity during checkout for group tickets', function () {
    $event = Event::factory()->create(['seat_capacity' => 10]);
    $ticketType = TicketType::factory()->groupTicket(4)->create([
        'event_id' => $event->id,
        'seats_per_ticket' => 2,
    ]);

    // Each ticket consumes 2 * 4 = 8 seats
    // Creating one ticket should consume 8 of 10 seats
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);
    Ticket::factory()->create([
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'owner_id' => $user->id,
        'order_id' => $order->id,
    ]);

    expect($event->fresh()->remainingSeatCapacity())->toBe(2);
});

// ---- Check-In ----

it('performs individual check-in for one user on a group ticket', function () {
    $action = app(UpdateTicketAssignments::class);

    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3, 'individual')->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $users = User::factory()->count(3)->create();
    foreach ($users as $user) {
        $ticket->users()->attach($user->id);
    }

    $action->checkIn($ticket, 1, $users[0]->id);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Active); // Not all checked in yet
    expect($ticket->users()->find($users[0]->id)->pivot->checked_in_at)->not->toBeNull();
    expect($ticket->users()->find($users[1]->id)->pivot->checked_in_at)->toBeNull();
});

it('marks ticket as CheckedIn when all users individually checked in', function () {
    $action = app(UpdateTicketAssignments::class);

    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(2, 'individual')->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $users = User::factory()->count(2)->create();
    foreach ($users as $user) {
        $ticket->users()->attach($user->id);
    }

    $action->checkIn($ticket->fresh(), 1, $users[0]->id);
    $action->checkIn($ticket->fresh(), 1, $users[1]->id);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::CheckedIn);
    expect($ticket->checked_in_at)->not->toBeNull();
});

it('performs group check-in marking all users at once', function () {
    $action = app(UpdateTicketAssignments::class);

    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3, 'group')->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $users = User::factory()->count(3)->create();
    foreach ($users as $user) {
        $ticket->users()->attach($user->id);
    }

    $action->checkIn($ticket->fresh(), 1);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::CheckedIn);
    expect($ticket->checked_in_at)->not->toBeNull();

    foreach ($users as $user) {
        expect($ticket->users()->find($user->id)->pivot->checked_in_at)->not->toBeNull();
    }
});

// ---- Authorization ----

it('allows owner to add users to their group ticket', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($owner)
        ->post("/tickets/{$ticket->id}/users", [
            'user_email' => $user->email,
        ])
        ->assertRedirect();
});

it('allows manager to add users to a group ticket', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $manager = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'manager_id' => $manager->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($manager)
        ->post("/tickets/{$ticket->id}/users", [
            'user_email' => $user->email,
        ])
        ->assertRedirect();
});

it('denies non-owner/manager from adding users', function () {
    $owner = User::factory()->withRole(RoleName::User)->create();
    $other = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->groupTicket(3)->create(['event_id' => $event->id]);
    $order = Order::factory()->create(['user_id' => $owner->id, 'event_id' => $event->id]);

    $ticket = Ticket::factory()->create([
        'owner_id' => $owner->id,
        'ticket_type_id' => $ticketType->id,
        'event_id' => $event->id,
        'order_id' => $order->id,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($other)
        ->post("/tickets/{$ticket->id}/users", [
            'user_email' => $user->email,
        ])
        ->assertForbidden();
});
