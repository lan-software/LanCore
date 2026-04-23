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
    $this->plan = SeatPlan::factory()->create([
        'event_id' => $this->event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
                ['id' => 'X1', 'title' => 'X1', 'x' => 9, 'y' => 0, 'salable' => false],
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

it('assigns a seat for the ticket owner when no users are added', function (): void {
    $assignment = $this->action->execute($this->ticket, $this->owner, $this->plan, 'A1');

    expect($assignment)->toBeInstanceOf(SeatAssignment::class)
        ->and($assignment->seat_id)->toBe('A1')
        ->and($assignment->user_id)->toBe($this->owner->id);
});

it('moves an existing assignment when the same (ticket,user) pair is reassigned', function (): void {
    $this->action->execute($this->ticket, $this->owner, $this->plan, 'A1');
    $this->action->execute($this->ticket, $this->owner, $this->plan, 'A2');

    expect(SeatAssignment::query()->where('ticket_id', $this->ticket->id)->count())->toBe(1)
        ->and(SeatAssignment::query()->where('ticket_id', $this->ticket->id)->first()->seat_id)->toBe('A2');
});

it('rejects double-booking the same seat across different tickets', function (): void {
    $otherUser = User::factory()->create();
    $otherTicket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->ticketType->id,
        'owner_id' => $otherUser->id,
    ]);

    $this->action->execute($this->ticket, $this->owner, $this->plan, 'A1');

    expect(fn () => $this->action->execute($otherTicket, $otherUser, $this->plan, 'A1'))
        ->toThrow(ValidationException::class);
});

it('rejects a non-salable seat', function (): void {
    expect(fn () => $this->action->execute($this->ticket, $this->owner, $this->plan, 'X1'))
        ->toThrow(ValidationException::class);
});

it('rejects a seat that does not exist on the plan', function (): void {
    expect(fn () => $this->action->execute($this->ticket, $this->owner, $this->plan, 'NOPE'))
        ->toThrow(ValidationException::class);
});

it('rejects a seat plan that belongs to a different event', function (): void {
    $otherPlan = SeatPlan::factory()->create([
        'data' => ['blocks' => [
            ['id' => 'b', 'title' => 'B', 'color' => '#fff', 'seats' => [
                ['id' => 'B1', 'title' => 'B1', 'x' => 0, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);

    expect(fn () => $this->action->execute($this->ticket, $this->owner, $otherPlan, 'B1'))
        ->toThrow(ValidationException::class);
});

it('rejects an assignee that is not on the ticket', function (): void {
    $stranger = User::factory()->create();

    expect(fn () => $this->action->execute($this->ticket, $stranger, $this->plan, 'A1'))
        ->toThrow(ValidationException::class);
});
