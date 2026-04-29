<?php

// Covers EVT-F-012 / EVT-T-012 — see docs/mil-std-498/SRS.md, STD.md (§4.30)

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;

it('returns an RFC 5545 calendar for a published event', function () {
    $address = Address::factory()->create([
        'street' => 'Game Street 1',
        'zip_code' => '12345',
        'city' => 'Cologne',
        'state' => 'NRW',
        'country' => 'Germany',
    ]);
    $venue = Venue::factory()->create([
        'name' => 'Acme Arena',
        'address_id' => $address->id,
    ]);
    $event = Event::factory()->create([
        'name' => 'Summer LAN 2026',
        'description' => 'Three days of competitive gaming.',
        'start_date' => '2026-07-01 10:00:00',
        'end_date' => '2026-07-03 18:00:00',
        'venue_id' => $venue->id,
        'status' => EventStatus::Published,
    ]);

    $response = $this->get("/events/{$event->id}/calendar.ics");

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment', '.ics');

    $body = $response->getContent();

    expect($body)
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('VERSION:2.0')
        ->toContain('BEGIN:VEVENT')
        ->toContain('SUMMARY:Summer LAN 2026')
        ->toContain('DESCRIPTION:Three days of competitive gaming.')
        ->toContain('DTSTART')
        ->toContain('DTEND')
        ->toContain('LOCATION:Acme Arena')
        ->toContain('Game Street 1')
        ->toContain('END:VEVENT')
        ->toContain('END:VCALENDAR');
});

it('returns 404 for a draft event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
    ]);

    $this->get("/events/{$event->id}/calendar.ics")
        ->assertNotFound();
});

it('returns 404 for an unknown event id', function () {
    $this->get('/events/999999/calendar.ics')
        ->assertNotFound();
});

it('escapes RFC 5545 special characters in name and description', function () {
    $event = Event::factory()->withoutVenue()->create([
        'name' => 'Foo, Bar; Baz',
        'description' => "Line one\nLine two; with semicolon, and comma",
        'status' => EventStatus::Published,
    ]);

    $body = $this->get("/events/{$event->id}/calendar.ics")
        ->assertSuccessful()
        ->getContent();

    expect($body)
        ->toContain('SUMMARY:Foo\, Bar\; Baz')
        ->toContain('Line one\nLine two\; with semicolon\, and comma');
});

it('omits LOCATION when the event has no venue', function () {
    $event = Event::factory()->withoutVenue()->create([
        'status' => EventStatus::Published,
    ]);

    $body = $this->get("/events/{$event->id}/calendar.ics")
        ->assertSuccessful()
        ->getContent();

    expect($body)
        ->toContain('BEGIN:VEVENT')
        ->not->toContain('LOCATION:');
});
