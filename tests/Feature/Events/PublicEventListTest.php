<?php

use App\Domain\Event\Models\Event;

it('allows guests to view the public events page', function () {
    Event::factory()->published()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]);

    $this->get('/upcoming-events')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Public')
                ->has('events.data', 1)
        );
});

it('only shows published events', function () {
    Event::factory()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]); // draft

    Event::factory()->published()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]);

    $this->get('/upcoming-events')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Public')
                ->has('events.data', 1)
        );
});

it('only shows upcoming events', function () {
    Event::factory()->published()->create([
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addDays(2),
    ]); // past

    Event::factory()->published()->create([
        'start_date' => now()->addWeek(),
        'end_date' => now()->addWeek()->addDays(2),
    ]); // upcoming

    $this->get('/upcoming-events')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Public')
                ->has('events.data', 1)
        );
});
