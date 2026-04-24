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
    $this->plan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'A',
            'color' => '#fff',
            'rows' => [
                ['name' => 'A', 'seats' => [
                    ['number' => 1, 'title' => 'Row A — Seat 1', 'x' => 0, 'y' => 0, 'salable' => true],
                    ['number' => 2, 'title' => 'Row A — Seat 2', 'x' => 1, 'y' => 0, 'salable' => true],
                ]],
            ],
        ],
    ])->create(['event_id' => $this->event->id]);

    $this->owner = User::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $this->owner->id,
    ]);

    $this->seatA1 = $this->plan->seats()->where('title', 'Row A — Seat 1')->firstOrFail();
    $this->seatA2 = $this->plan->seats()->where('title', 'Row A — Seat 2')->firstOrFail();
});

it('resolves seat_title from the related seat when loaded', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_plan_seat_id' => $this->seatA1->id,
    ]);

    $assignment->load('seat');

    expect($assignment->seat_title)->toBe('Row A — Seat 1');
});

it('returns null seat_title when the seat relation is not loaded', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_plan_seat_id' => $this->seatA1->id,
    ]);

    expect($assignment->relationLoaded('seat'))->toBeFalse()
        ->and($assignment->seat_title)->toBeNull();
});

it('prepends the block seat_title_prefix when block is loaded', function (): void {
    $this->plan->blocks()->first()->forceFill(['seat_title_prefix' => 'VIP-'])->save();

    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_plan_seat_id' => $this->seatA1->id,
    ]);

    $assignment->load('seat.block');

    expect($assignment->seat_title)->toBe('VIP-Row A — Seat 1');
});

it('includes seat_title in array/JSON serialization', function (): void {
    $assignment = SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->owner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_plan_seat_id' => $this->seatA2->id,
    ]);

    $assignment->load('seat');

    expect($assignment->toArray())->toHaveKey('seat_title', 'Row A — Seat 2');
});
