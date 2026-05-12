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
 * Backstops the "find on seat plan" deep-link. The profile's quick action
 * sends visitors to `/events/{event}/public?focus_user={id}`; the public
 * event page resolves that to a seat id (which the front-end pulses on the
 * canvas) — but ONLY when {@see User::isSeatNameVisibleTo()} allows it.
 */
function publishedEventWithSeatedUser(User $user, bool $publicSeat): array
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
        'title' => 'A-7',
    ]);

    $ticket = Ticket::factory()->for($event)->for($user, 'owner')->create();

    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seat->id,
    ]);

    return [$event, $seat];
}

it('resolves focus_user to the seat id when the seat is publicly visible', function () {
    $user = User::factory()->create();
    [$event, $seat] = publishedEventWithSeatedUser($user, publicSeat: true);

    $this->get("/events/{$event->id}/public?focus_user={$user->id}")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->where('focusSeatId', $seat->id),
        );
});

it('ships seat plans with nested blocks/seats so the canvas can render', function () {
    $user = User::factory()->create();
    [$event] = publishedEventWithSeatedUser($user, publicSeat: true);

    $this->get("/events/{$event->id}/public")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->has('nextEvent.seat_plans.0.blocks.0.seats.0')
                ->has('nextEvent.taken_seats', 1),
        );
});

it('omits focusSeatId for an anonymous viewer when the seat is private', function () {
    $user = User::factory()->create();
    [$event] = publishedEventWithSeatedUser($user, publicSeat: false);

    $this->get("/events/{$event->id}/public?focus_user={$user->id}")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->where('focusSeatId', null),
        );
});

it('reveals the seat to another attendee even when the seat is private', function () {
    $user = User::factory()->create();
    [$event, $seat] = publishedEventWithSeatedUser($user, publicSeat: false);

    $viewer = User::factory()->create();
    Ticket::factory()->for($event)->for($viewer, 'owner')->create();

    $this->actingAs($viewer)
        ->get("/events/{$event->id}/public?focus_user={$user->id}")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->where('focusSeatId', $seat->id),
        );
});

it('returns null focusSeatId when no focus_user is supplied', function () {
    $user = User::factory()->create();
    [$event] = publishedEventWithSeatedUser($user, publicSeat: true);

    $this->get("/events/{$event->id}/public")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('focusSeatId', null),
        );
});

it('returns null focusSeatId when focus_user has no assignment for the event', function () {
    $user = User::factory()->create(['is_seat_visible_publicly' => true]);

    $event = Event::factory()->published()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]);

    $this->get("/events/{$event->id}/public?focus_user={$user->id}")
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('focusSeatId', null),
        );
});
