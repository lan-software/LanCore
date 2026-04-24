<?php

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatPlan;

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
