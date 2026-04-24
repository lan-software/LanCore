<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Actions\AssignSeat;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->action = app(AssignSeat::class);
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
                    ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                    ['number' => 2, 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
                    ['number' => 3, 'title' => 'X1', 'x' => 9, 'y' => 0, 'salable' => false],
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

    $this->seatA1 = $this->plan->seats()->where('title', 'A1')->firstOrFail();
    $this->seatA2 = $this->plan->seats()->where('title', 'A2')->firstOrFail();
    $this->seatX1 = $this->plan->seats()->where('title', 'X1')->firstOrFail();
});

it('assigns a seat for the ticket owner when no users are added', function (): void {
    $assignment = $this->action->execute($this->ticket, $this->owner, $this->plan, $this->seatA1->id);

    expect($assignment)->toBeInstanceOf(SeatAssignment::class)
        ->and($assignment->seat_plan_seat_id)->toBe($this->seatA1->id)
        ->and($assignment->user_id)->toBe($this->owner->id);
});

it('moves an existing assignment when the same (ticket,user) pair is reassigned', function (): void {
    $this->action->execute($this->ticket, $this->owner, $this->plan, $this->seatA1->id);
    $this->action->execute($this->ticket, $this->owner, $this->plan, $this->seatA2->id);

    expect(SeatAssignment::query()->where('ticket_id', $this->ticket->id)->count())->toBe(1)
        ->and(SeatAssignment::query()->where('ticket_id', $this->ticket->id)->first()->seat_plan_seat_id)->toBe($this->seatA2->id);
});

it('rejects double-booking the same seat across different tickets', function (): void {
    $otherUser = User::factory()->create();
    $otherTicket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $otherUser->id,
    ]);

    $this->action->execute($this->ticket, $this->owner, $this->plan, $this->seatA1->id);

    expect(fn () => $this->action->execute($otherTicket, $otherUser, $this->plan, $this->seatA1->id))
        ->toThrow(ValidationException::class);
});

it('rejects a non-salable seat', function (): void {
    expect(fn () => $this->action->execute($this->ticket, $this->owner, $this->plan, $this->seatX1->id))
        ->toThrow(ValidationException::class);
});

it('rejects a seat that does not exist on the plan', function (): void {
    expect(fn () => $this->action->execute($this->ticket, $this->owner, $this->plan, 999999))
        ->toThrow(ValidationException::class);
});

it('rejects a seat plan that belongs to a different event', function (): void {
    $otherPlan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'B',
            'color' => '#fff',
            'rows' => [
                ['name' => 'B', 'seats' => [
                    ['number' => 1, 'title' => 'B1', 'x' => 0, 'y' => 0, 'salable' => true],
                ]],
            ],
        ],
    ])->create();

    $otherSeat = $otherPlan->seats()->first();

    expect(fn () => $this->action->execute($this->ticket, $this->owner, $otherPlan, $otherSeat->id))
        ->toThrow(ValidationException::class);
});

it('rejects an assignee that is not on the ticket', function (): void {
    $stranger = User::factory()->create();

    expect(fn () => $this->action->execute($this->ticket, $stranger, $this->plan, $this->seatA1->id))
        ->toThrow(ValidationException::class);
});
