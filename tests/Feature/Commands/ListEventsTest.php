<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;

it('lists all events in a table', function () {
    $event = Event::factory()->create(['name' => 'Summer LAN', 'status' => EventStatus::Published]);

    $this->artisan('events:list')
        ->expectsTable(
            ['ID', 'Name', 'Status', 'Venue', 'Start', 'End', 'Seats'],
            [
                [
                    $event->id,
                    'Summer LAN',
                    'published',
                    $event->venue?->name ?? '-',
                    $event->start_date?->format('Y-m-d H:i') ?? '-',
                    $event->end_date?->format('Y-m-d H:i') ?? '-',
                    $event->seat_capacity ?? '-',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters events by status', function () {
    Event::factory()->create(['status' => EventStatus::Draft]);
    $published = Event::factory()->create(['status' => EventStatus::Published]);

    $this->artisan('events:list --status=published')
        ->expectsOutputToContain($published->name)
        ->assertSuccessful();
});

it('shows error for invalid status', function () {
    $this->artisan('events:list --status=invalid')
        ->expectsOutputToContain("Invalid status 'invalid'")
        ->assertFailed();
});

it('filters events by venue', function () {
    $event = Event::factory()->create();
    Event::factory()->create();

    $this->artisan("events:list --venue={$event->venue_id}")
        ->expectsOutputToContain($event->name)
        ->assertSuccessful();
});

it('shows message when no events found', function () {
    $this->artisan('events:list')
        ->expectsOutputToContain('No events found.')
        ->assertSuccessful();
});
