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
    $this->plan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'A',
            'color' => '#fff',
            'rows' => [
                ['name' => 'A', 'seats' => [
                    ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                    ['number' => 2, 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
                    ['number' => 3, 'title' => 'A3', 'x' => 2, 'y' => 0, 'salable' => true],
                ]],
            ],
        ],
    ])->create(['event_id' => $this->event->id]);

    $this->seatA1 = $this->plan->seats()->where('title', 'A1')->firstOrFail();
    $this->seatA2 = $this->plan->seats()->where('title', 'A2')->firstOrFail();
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
            'seat_id' => $this->seatA1->id,
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
            'seat_id' => $this->seatA2->id,
        ])
        ->assertRedirect();

    $assignment = SeatAssignment::query()->where('ticket_id', $ticket->id)->first();
    expect($assignment->user_id)->toBe($assignee->id)
        ->and($assignment->seat_plan_seat_id)->toBe($this->seatA2->id);
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

    $this->actingAs($assignee)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatA1->id,
        ])
        ->assertRedirect();

    $this->actingAs($assignee)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $otherAssignee->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatA2->id,
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
            'seat_id' => $this->seatA1->id,
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
        'seat_plan_seat_id' => $this->seatA1->id,
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
        'seat_plan_seat_id' => $this->seatA1->id,
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

    SeatAssignment::factory()->create([
        'ticket_id' => $ticketA->id,
        'user_id' => $ownerA->id,
        'seat_plan_id' => $this->plan->id,
        'seat_plan_seat_id' => $this->seatA1->id,
    ]);

    $this->actingAs($ownerB)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticketB->id,
            'user_id' => $ownerB->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatA1->id,
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
        'seat_plan_seat_id' => $this->seatA1->id,
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
