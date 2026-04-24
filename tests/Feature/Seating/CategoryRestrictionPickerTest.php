<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

beforeEach(function (): void {
    $this->event = Event::factory()->create();
    $this->vipCategory = TicketCategory::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'VIP',
    ]);
    $this->standardCategory = TicketCategory::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Standard',
    ]);

    $this->vipType = TicketType::factory()->create([
        'event_id' => $this->event->id,
        'ticket_category_id' => $this->vipCategory->id,
        'max_users_per_ticket' => 1,
    ]);
    $this->standardType = TicketType::factory()->create([
        'event_id' => $this->event->id,
        'ticket_category_id' => $this->standardCategory->id,
        'max_users_per_ticket' => 1,
    ]);

    $this->plan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'VIP',
            'color' => '#fff',
            'allowed_ticket_category_ids' => [$this->vipCategory->id],
            'rows' => [['name' => 'V', 'seats' => [['number' => 1, 'title' => 'V1', 'x' => 0, 'y' => 0, 'salable' => true]]]],
        ],
        [
            'title' => 'Open',
            'color' => '#fff',
            'rows' => [['name' => 'O', 'seats' => [['number' => 1, 'title' => 'O1', 'x' => 0, 'y' => 0, 'salable' => true]]]],
        ],
    ])->create(['event_id' => $this->event->id]);

    $this->seatV1 = $this->plan->seats()->where('title', 'V1')->firstOrFail();
    $this->seatO1 = $this->plan->seats()->where('title', 'O1')->firstOrFail();
});

it('rejects a Standard ticket seating into the VIP block', function (): void {
    $owner = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->standardType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatV1->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('seat_id');

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(0);
});

it('allows a VIP ticket to seat into the VIP block', function (): void {
    $owner = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->vipType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatV1->id,
        ])
        ->assertRedirect();

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});

it('allows any ticket to seat into a block with no allowlist (permissive default)', function (): void {
    $owner = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->standardType->id,
        'owner_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post("/events/{$this->event->id}/seats", [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'seat_plan_id' => $this->plan->id,
            'seat_id' => $this->seatO1->id,
        ])
        ->assertRedirect();

    expect(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
