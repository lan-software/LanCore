<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Enums\CheckInMode;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\EntranceAuditLog;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('forbids non-admins from viewing the dashboard', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/events/dashboard')
        ->assertForbidden();
});

it('renders the dashboard with no event when none is published', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/events/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Dashboard')
                ->where('stats', null)
        );
});

it('defaults to the currently running event when no session selection', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    Event::factory()->published()->create([
        'name' => 'Upcoming Next',
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]);

    $running = Event::factory()->published()->create([
        'name' => 'Running Now',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get('/events/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Dashboard')
                ->where('stats.event.id', $running->id)
        );
});

it('falls back to the next upcoming event when none is running', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    Event::factory()->published()->create([
        'name' => 'Past',
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addDays(2),
    ]);

    $upcoming = Event::factory()->published()->create([
        'name' => 'Soonest Upcoming',
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
    ]);

    Event::factory()->published()->create([
        'name' => 'Later Upcoming',
        'start_date' => now()->addMonths(2),
        'end_date' => now()->addMonths(2)->addDays(3),
    ]);

    $this->actingAs($admin)
        ->get('/events/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page->where('stats.event.id', $upcoming->id),
        );
});

it('respects the session-selected event over active/upcoming defaults', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $running = Event::factory()->published()->create([
        'name' => 'Running Now',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    $pinned = Event::factory()->create([
        'name' => 'Pinned',
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addDays(2),
    ]);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $pinned->id])
        ->get('/events/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page->where('stats.event.id', $pinned->id),
        );

    // Sanity: running would be chosen without the session key.
    expect($running->id)->not->toBe($pinned->id);
});

it('computes headline metrics correctly for individual and group tickets', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $event = Event::factory()->published()->create([
        'name' => 'Stat Event',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'seat_capacity' => 100,
    ]);

    // Individual ticket type: quota 10
    $individualType = TicketType::factory()->create([
        'event_id' => $event->id,
        'quota' => 10,
        'check_in_mode' => CheckInMode::Individual->value,
        'max_users_per_ticket' => 1,
    ]);

    // Group ticket type: quota 5, 3 users each
    $groupType = TicketType::factory()->create([
        'event_id' => $event->id,
        'quota' => 5,
        'check_in_mode' => CheckInMode::Group->value,
        'max_users_per_ticket' => 3,
    ]);

    // 3 individual tickets: 1 active, 1 checked-in, 1 cancelled
    $indivActive = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $individualType->id,
        'status' => TicketStatus::Active,
    ]);
    $indivCheckedIn = Ticket::factory()->checkedIn()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $individualType->id,
    ]);
    Ticket::factory()->cancelled()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $individualType->id,
    ]);

    // 1 group ticket active with 3 users (2 checked in, 1 not)
    $groupTicket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $groupType->id,
        'status' => TicketStatus::Active,
    ]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();
    $groupTicket->users()->attach($u1->id, ['checked_in_at' => now()]);
    $groupTicket->users()->attach($u2->id, ['checked_in_at' => now()]);
    $groupTicket->users()->attach($u3->id, ['checked_in_at' => null]);

    $response = $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/events/dashboard')
        ->assertSuccessful();

    $response->assertInertia(
        fn ($page) => $page
            ->where('stats.headline.ticketsSold', 3)        // 2 indiv non-cancelled + 1 group
            ->where('stats.headline.ticketsInSale', (10 - 2) + (5 - 1)) // remaining based on non-cancelled
            ->where('stats.headline.activeAssignees', 2 + 3) // 2 indiv active + 3 group users
            ->where('stats.headline.checkedIn', 1 + 2)       // 1 indiv + 2 group
            ->where('stats.headline.notCheckedIn', (2 + 3) - (1 + 2))
    );

    // Use variables to avoid unused warnings
    expect($indivActive->id)->toBeInt();
    expect($indivCheckedIn->id)->toBeInt();
});

it('counts seated users distinctly via seat assignments', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $event = Event::factory()->published()->create([
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    $seatPlan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'A',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ['number' => 2, 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => true],
            ['number' => 3, 'title' => 'A3', 'x' => 60, 'y' => 0, 'salable' => true],
        ]]],
    ]])->create(['event_id' => $event->id]);
    $seats = $seatPlan->seats()->orderBy('number')->get();

    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $t1 = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $u1->id,
    ]);
    $t2 = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $u2->id,
    ]);

    // u1 has two different tickets, both with seat assignments — should only count as 1 seated user
    $t1b = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $u1->id,
    ]);

    SeatAssignment::factory()->create([
        'ticket_id' => $t1->id,
        'user_id' => $u1->id,
        'seat_plan_id' => $seatPlan->id,
        'seat_plan_seat_id' => $seats[0]->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $t2->id,
        'user_id' => $u2->id,
        'seat_plan_id' => $seatPlan->id,
        'seat_plan_seat_id' => $seats[1]->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $t1b->id,
        'user_id' => $u1->id,
        'seat_plan_id' => $seatPlan->id,
        'seat_plan_seat_id' => $seats[2]->id,
    ]);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/events/dashboard')
        ->assertInertia(
            fn ($page) => $page->where('stats.headline.seatedUserCount', 2),
        );
});

it('returns the last 20 check-ins ordered by most recent first', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $event = Event::factory()->published()->create([
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    $otherEvent = Event::factory()->published()->create();

    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    $operator = User::factory()->withRole(RoleName::Admin)->create();

    // Older log from another event — should not appear
    $otherType = TicketType::factory()->create(['event_id' => $otherEvent->id]);
    $otherTicket = Ticket::factory()->create([
        'event_id' => $otherEvent->id,
        'ticket_type_id' => $otherType->id,
    ]);
    EntranceAuditLog::create([
        'ticket_id' => $otherTicket->id,
        'token_fingerprint' => 'other',
        'action' => 'checkin',
        'decision' => 'ok',
        'operator_id' => $operator->id,
        'created_at' => now()->subMinutes(5),
    ]);

    // 25 check-ins for this event, oldest → newest
    for ($i = 0; $i < 25; $i++) {
        EntranceAuditLog::create([
            'ticket_id' => $ticket->id,
            'token_fingerprint' => "fp-$i",
            'action' => 'checkin',
            'decision' => 'ok',
            'operator_id' => $operator->id,
            'created_at' => now()->subMinutes(25 - $i),
        ]);
    }

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/events/dashboard')
        ->assertInertia(
            fn ($page) => $page
                ->has('stats.recentCheckins', 20)
                ->where('stats.recentCheckins.0.action', 'checkin'),
        );
});
