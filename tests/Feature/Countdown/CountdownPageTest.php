<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Newsletter\Models\NewsletterList;

it('renders an upcoming-event countdown for guests', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'start_date' => now()->addDays(20),
        'end_date' => now()->addDays(22),
        'name' => 'Summer LAN 2026',
    ]);

    NewsletterList::factory()->defaultPublic()->create([
        'name' => 'Summer Announcements',
    ]);

    $this->get('/countdown')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Countdown')
            ->where('event.id', $event->id)
            ->where('event.name', 'Summer LAN 2026')
            ->where('newsletter.list_name', 'Summer Announcements'),
        );
});

it('renders a quiet placeholder when no upcoming event exists', function (): void {
    $this->get('/countdown')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Countdown')
            ->where('event', null),
        );
});
