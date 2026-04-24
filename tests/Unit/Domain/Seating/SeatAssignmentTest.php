<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

beforeEach(function (): void {
    $this->event = Event::factory()->create();
    $this->ticketType = TicketType::factory()->create([
        'event_id' => $this->event->id,
        'max_users_per_ticket' => 1,
    ]);
    $this->plan = SeatPlan::factory()->create([
        'event_id' => $this->event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'Row A — Seat 1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'Row A — Seat 2', 'x' => 1, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);
    $this->owner = User::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $this->owner->id,
    ]);
});

it('resolves seat_title from the related seat plan when loaded', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    $assignment->load('seatPlan');

    expect($assignment->seat_title)->toBe('Row A — Seat 1');
});

it('returns null seat_title when the seat id is not present on the plan', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    $assignment->load('seatPlan');
    $assignment->seat_id = 'UNKNOWN';

    expect($assignment->seat_title)->toBeNull();
});

it('returns null seat_title when the seat plan relation is not loaded', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);

    expect($assignment->relationLoaded('seatPlan'))->toBeFalse()
        ->and($assignment->seat_title)->toBeNull();
});

it('includes seat_title in array/JSON serialization', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A2',
    ]);

    $assignment->load('seatPlan');

    expect($assignment->toArray())->toHaveKey('seat_title', 'Row A — Seat 2');
});
