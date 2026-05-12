<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanBlock;
use App\Domain\Seating\Models\SeatPlanSeat;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Covers the "find a friend's seat" quick action on the public profile.
 *
 * The seat block is only attached when {@see User::isSeatNameVisibleTo()}
 * grants the viewer access. Two cases:
 *   - Public seat visibility → anonymous viewers see the seat label.
 *   - Private seat visibility → only same-event attendees (and self) see it.
 */
function seatedUpcomingEvent(User $user, string $seatTitle, bool $publicSeat): Event
{
    $user->update(['is_seat_visible_publicly' => $publicSeat]);

    $event = Event::factory()->published()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]);

    $plan = SeatPlan::factory()->create(['event_id' => $event->id]);
    $block = SeatPlanBlock::factory()->create([
        'seat_plan_id' => $plan->id,
        'seat_title_prefix' => '',
    ]);
    $seat = SeatPlanSeat::factory()->create([
        'seat_plan_id' => $plan->id,
        'seat_plan_block_id' => $block->id,
        'title' => $seatTitle,
    ]);

    $ticket = Ticket::factory()->for($event)->for($user, 'owner')->create();

    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seat->id,
    ]);

    return $event;
}

test('public seat visibility exposes the seat on the public profile', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    seatedUpcomingEvent($user, 'A-7', publicSeat: true);

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.seat.seat_title', 'A-7')
        );
});

test('private seat visibility hides the seat from anonymous viewers', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    seatedUpcomingEvent($user, 'A-7', publicSeat: false);

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->has('upcomingEvents', 1)
                ->missing('upcomingEvents.0.seat')
        );
});

test('private seat is visible to a viewer who also has a ticket for the same event', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $event = seatedUpcomingEvent($user, 'A-7', publicSeat: false);

    $viewer = User::factory()->create();
    Ticket::factory()->for($event)->for($viewer, 'owner')->create();

    $this->actingAs($viewer)
        ->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->where('upcomingEvents.0.seat.seat_title', 'A-7')
                ->where(
                    'upcomingEvents.0.seat.event_url',
                    route('events.public.show', ['event' => $event->id]).'?focus_user='.$user->id,
                )
        );
});

test('private seat stays hidden from a logged-in viewer with no ticket for the event', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    seatedUpcomingEvent($user, 'A-7', publicSeat: false);

    $bystander = User::factory()->create();

    $this->actingAs($bystander)
        ->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->missing('upcomingEvents.0.seat')
        );
});
