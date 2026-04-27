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
test('upcoming events lists future events the user has a ticket for', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $upcoming = Event::factory()->published()->create([
        'name' => 'Summer LAN 2026',
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDays(2),
    ]);

    Ticket::factory()->for($upcoming)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('u/Show')
                ->has('upcomingEvents', 1)
                ->where('upcomingEvents.0.name', 'Summer LAN 2026')
                ->where('upcomingEvents.0.public_url', route('events.public.show', ['event' => $upcoming->id]))
        );
});

test('upcoming events excludes past events', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $past = Event::factory()->published()->create([
        'start_date' => now()->subMonths(2),
        'end_date' => now()->subMonths(2)->addDays(2),
    ]);

    Ticket::factory()->for($past)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->has('upcomingEvents', 0)
        );
});

test('upcoming events excludes events the user has no participation in', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $stranger = User::factory()->create();
    $upcoming = Event::factory()->published()->create([
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDay(),
    ]);

    Ticket::factory()->for($upcoming)->for($stranger, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->has('upcomingEvents', 0)
        );
});

test('upcoming events excludes unpublished events even when user holds a ticket', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $draft = Event::factory()->create([
        'status' => EventStatus::Draft,
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDay(),
    ]);

    Ticket::factory()->for($draft)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page->has('upcomingEvents', 0)
        );
});

test('upcoming events are sorted earliest first', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $later = Event::factory()->published()->create([
        'name' => 'Later LAN',
        'start_date' => now()->addMonths(3),
        'end_date' => now()->addMonths(3)->addDay(),
    ]);
    $sooner = Event::factory()->published()->create([
        'name' => 'Sooner LAN',
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDay(),
    ]);

    Ticket::factory()->for($later)->for($user, 'owner')->create();
    Ticket::factory()->for($sooner)->for($user, 'owner')->create();

    $this->get('/u/neo_42')
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('upcomingEvents', 2)
                ->where('upcomingEvents.0.name', 'Sooner LAN')
                ->where('upcomingEvents.1.name', 'Later LAN')
        );
});
