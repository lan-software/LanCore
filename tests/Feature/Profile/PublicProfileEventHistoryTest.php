<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * @see docs/mil-std-498/STD.md §4.24 Public Profile Tests
 * @see docs/mil-std-498/SRS.md USR-F-023
 */
test('event history lists past events the user owned a ticket for', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $pastEvent = Event::factory()->published()->create([
        'name' => 'Retro LAN 2024',
        'start_date' => now()->subMonths(3),
        'end_date' => now()->subMonths(3)->addDays(2),
    ]);

    Ticket::factory()->for($pastEvent)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->has('eventHistory', 1)
                ->where('eventHistory.0.name', 'Retro LAN 2024')
                ->where('eventHistory.0.public_url', route('events.public.show', ['event' => $pastEvent->id]))
        );
});

test('event history excludes future events', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $futureEvent = Event::factory()->published()->create([
        'start_date' => now()->addMonths(2),
        'end_date' => now()->addMonths(2)->addDays(2),
    ]);

    Ticket::factory()->for($futureEvent)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->has('eventHistory', 0)
        );
});

test('event history excludes events the user did not participate in', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $stranger = User::factory()->create();
    $pastEvent = Event::factory()->published()->create([
        'start_date' => now()->subMonths(1),
        'end_date' => now()->subMonths(1)->addDay(),
    ]);

    Ticket::factory()->for($pastEvent)->for($stranger, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->has('eventHistory', 0)
        );
});

test('event history is sorted by start_date descending', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $older = Event::factory()->published()->create([
        'name' => 'Older LAN',
        'start_date' => now()->subYear(),
        'end_date' => now()->subYear()->addDays(2),
    ]);
    $newer = Event::factory()->published()->create([
        'name' => 'Newer LAN',
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addDays(2),
    ]);

    Ticket::factory()->for($older)->for($user, 'owner')->create();
    Ticket::factory()->for($newer)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('eventHistory', 2)
                ->where('eventHistory.0.name', 'Newer LAN')
                ->where('eventHistory.1.name', 'Older LAN')
        );
});

test('event history omits public_url for unpublished past events', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $draftPast = Event::factory()->create([
        'status' => EventStatus::Draft,
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addDay(),
    ]);

    Ticket::factory()->for($draftPast)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('eventHistory', 1)
                ->where('eventHistory.0.public_url', null)
        );
});
