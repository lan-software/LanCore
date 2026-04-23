<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

beforeEach(function (): void {
    $this->event = Event::factory()->create();
    $this->ticketType = TicketType::factory()->create([
        'event_id' => $this->event->id,
        'max_users_per_ticket' => 3,
    ]);
    $this->plan = SeatPlan::factory()->create([
        'event_id' => $this->event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
                ['id' => 'A3', 'title' => 'A3', 'x' => 2, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);
});

it('owner picks a seat for themselves', function (): void {
    $owner = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A1',
        ])
        ->assertRedirect();

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});

it('manager picks a seat for an assigned user on a group ticket', function (): void {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
        'manager_id' => $manager->id,
    ]);
    $ticket->users()->attach($assignee->id);

    $this->actingAs($manager)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A2',
        ])
        ->assertRedirect();

    $assignment = SeatAssignment::query()->where('ticket_id', $ticket->id)->first();
    expect($assignment->user_id)->toBe($assignee->id)
        ->and($assignment->seat_id)->toBe('A2');
});

it('an assigned user can pick their own seat but not someone elses on the same ticket', function (): void {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $otherAssignee = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);
    $ticket->users()->attach([$assignee->id, $otherAssignee->id]);

    // Self-assign succeeds
    $this->actingAs($assignee)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A1',
        ])
        ->assertRedirect();

    // Trying to seat the other user fails
    $this->actingAs($assignee)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $otherAssignee->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A2',
        ])
        ->assertForbidden();
});

it('a stranger cannot pick a seat on a ticket they do not belong to', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($stranger)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $stranger->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A1',
        ])
        ->assertForbidden();
});

it('cancelling a ticket releases all its seat assignments', function (): void {
    $owner = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);

    $ticket->update(['status' => TicketStatus::Cancelled]);

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(0);
});

it('removing a user from a group ticket releases their seat', function (): void {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);
    $ticket->users()->attach($assignee->id);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $assignee->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    $this->actingAs($owner)
        ->delete("/tickets/{$ticket->id}/users/{$assignee->id}")
        ->assertRedirect();

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->where('user_id', $assignee->id)->count())->toBe(0);
});

it('flashes a seat_id validation error into the session when the seat is already taken', function (): void {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();

    $ticketA = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $ownerA->id,
    ]);
    $ticketB = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $ownerB->id,
    ]);

    // Seat A1 already assigned to owner A on ticket A.
    SeatAssignment::factory()->create([
        'ticket_id' => $ticketA->id,
        'user_id' => $ownerA->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    // Owner B tries to grab the same seat via their own ticket.
    $this->actingAs($ownerB)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticketB->id,
            'user_id' => $ownerB->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => 'A1',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('seat_id');
});

it('returns the picker page with my tickets and visible-name overlay', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create(['is_seat_visible_publicly' => true]);
    $otherTicket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $other->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $otherTicket->id,
        'user_id' => $other->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get("/events/{$this->event->id}/seats")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('seating/Picker')
            ->where('event.id', $this->event->id)
            ->has('myTickets', 1)
            ->has('taken', 1)
            ->where('taken.0.name', $other->name),
        );
});
