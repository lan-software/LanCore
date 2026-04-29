<?php

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

it('shows the welcome page with no upcoming event', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('nextEvent', null)
        );
});

it('shows the next upcoming published event', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('nextEvent.id', $event->id)
        );
});

it('includes public programs and time slots for the upcoming event', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $publicProgram = Program::factory()->for($event)->create(['visibility' => 'public']);
    $privateProgram = Program::factory()->for($event)->create(['visibility' => 'private']);

    TimeSlot::factory()->for($publicProgram)->create(['visibility' => 'public']);
    TimeSlot::factory()->for($publicProgram)->create(['visibility' => 'private']);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('nextEvent.programs', 1)
                ->has('nextEvent.programs.0.time_slots', 1)
        );
});

it('does not show past events', function () {
    Event::factory()->published()->create([
        'start_date' => now()->subDays(2),
        'end_date' => now()->subDays(1),
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('nextEvent', null)
        );
});

it('does not show draft events', function () {
    Event::factory()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->where('nextEvent', null)
        );
});

it('includes seat plans for the upcoming event', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    SeatPlan::factory()->for($event)->create();

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('nextEvent.seat_plans', 1)
                ->has('nextEvent.seat_plans.0.blocks', 1)
        );
});

it('exposes profile preview fields for publicly-visible seated users', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $plan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'A',
            'color' => '#fff',
            'rows' => [
                ['name' => 'A', 'seats' => [
                    ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ]],
            ],
        ],
    ])->create(['event_id' => $event->id]);
    $seat = $plan->seats()->where('title', 'A1')->firstOrFail();

    $occupant = User::factory()->create([
        'is_seat_visible_publicly' => true,
        'username' => 'public_occupant',
        'profile_emoji' => '🎲',
        'short_bio' => 'Visible bio.',
    ]);
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $occupant->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $occupant->id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seat->id,
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('nextEvent.taken_seats', 1)
                ->where('nextEvent.taken_seats.0.username', 'public_occupant')
                ->where('nextEvent.taken_seats.0.profile_emoji', '🎲')
                ->where('nextEvent.taken_seats.0.short_bio', 'Visible bio.')
                ->has('nextEvent.taken_seats.0.avatar_url')
        );
});

it('redacts seated user profile fields for anonymous viewers when seat is not publicly visible', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $plan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'A',
            'color' => '#fff',
            'rows' => [
                ['name' => 'A', 'seats' => [
                    ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ]],
            ],
        ],
    ])->create(['event_id' => $event->id]);
    $seat = $plan->seats()->where('title', 'A1')->firstOrFail();

    $occupant = User::factory()->create([
        'is_seat_visible_publicly' => false,
        'username' => 'private_occupant',
    ]);
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $occupant->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $occupant->id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seat->id,
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('nextEvent.taken_seats', 1)
                ->where('nextEvent.taken_seats.0.name', null)
                ->where('nextEvent.taken_seats.0.username', null)
                ->where('nextEvent.taken_seats.0.profile_emoji', null)
                ->where('nextEvent.taken_seats.0.short_bio', null)
                ->where('nextEvent.taken_seats.0.avatar_url', null)
                ->where('nextEvent.taken_seats.0.banner_url', null)
        );
});
