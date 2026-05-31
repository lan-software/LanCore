<?php

use App\Domain\Event\Enums\AttendanceMode;
use App\Domain\Event\Models\Event;
use App\Domain\Publishing\Actions\BuildLanPartyDocument;
use App\Domain\Shop\Support\CurrencyResolver;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;
use App\Models\OrganizationSetting;

/**
 * @see docs/mil-std-498/STD.md TC-PUB-001..009
 */
function lppsVenueWithPublishedEvent(array $addressAttributes = [], array $eventAttributes = []): array
{
    $address = Address::factory()->create($addressAttributes);
    $venue = Venue::factory()->create(['address_id' => $address->id]);
    $event = Event::factory()->published()->create([
        'venue_id' => $venue->id,
        ...$eventAttributes,
    ]);

    return [$venue, $event];
}

it('serves the LPPS document at the well-known path as JSON', function () {
    OrganizationSetting::set('name', 'Test LAN Org');
    OrganizationSetting::set('publisher_unique_id', 'test-lan-org');
    OrganizationSetting::set('steam_group_url', 'https://steamcommunity.com/groups/test');

    lppsVenueWithPublishedEvent(
        ['latitude' => 51.5072, 'longitude' => -0.1276, 'country_code' => 'GB'],
        ['name' => 'Published LAN', 'seat_capacity' => 100],
    );

    $response = $this->get('/.well-known/lan-party.json');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/json');

    $response->assertJsonPath('$schema', BuildLanPartyDocument::SCHEMA_URL);
    $response->assertJsonPath('organisation.apiVersion', 2);
    $response->assertJsonPath('organisation.apiType', 'Organisation');
    $response->assertJsonPath('organisation.publisherUniqueId', 'test-lan-org');
    $response->assertJsonPath('organisation.name', 'Test LAN Org');
    $response->assertJsonPath('organisation.steamGroupUrl', 'https://steamcommunity.com/groups/test');

    $response->assertJsonPath('organisation.venues.0.apiType', 'Venue');
    $response->assertJsonPath('organisation.venues.0.gpsLatitude', 51.5072);
    $response->assertJsonPath('organisation.venues.0.countryCode', 'GB');
    $response->assertJsonPath('organisation.venues.0.events.0.apiType', 'Event');
    $response->assertJsonPath('organisation.venues.0.events.0.name', 'Published LAN');
    $response->assertJsonPath('organisation.venues.0.events.0.maximumAttendeeCapacity', 100);
});

it('falls back to a slugged publisher id when none is configured', function () {
    OrganizationSetting::set('name', 'Cool LAN Crew');
    lppsVenueWithPublishedEvent();

    $this->get('/.well-known/lan-party.json')
        ->assertOk()
        ->assertJsonPath('organisation.publisherUniqueId', 'cool-lan-crew');
});

it('excludes draft events and venue-less published events', function () {
    $venue = Venue::factory()->create();
    Event::factory()->create(['name' => 'Draft One', 'venue_id' => $venue->id]);
    Event::factory()->published()->withoutVenue()->create(['name' => 'No Venue Event']);

    $this->get('/.well-known/lan-party.json')
        ->assertOk()
        ->assertJsonMissing(['name' => 'Draft One'])
        ->assertJsonMissing(['name' => 'No Venue Event']);
});

it('formats dates as ISO-8601 without a timezone offset', function () {
    lppsVenueWithPublishedEvent([], [
        'start_date' => '2026-07-15 16:00:00',
        'end_date' => '2026-07-18 14:00:00',
    ]);

    $event = $this->get('/.well-known/lan-party.json')->json('organisation.venues.0.events.0');

    expect($event['startDate'])->toBe('2026-07-15T16:00:00');
    expect($event['endDate'])->toBe('2026-07-18T14:00:00');
});

it('emits event amenity, policy and connectivity fields', function () {
    lppsVenueWithPublishedEvent([], [
        'attendance_mode' => AttendanceMode::Mixed,
        'sleeping' => 12,
        'has_showers' => true,
        'food_policy' => 4,
        'network_connection_mbps' => 1000,
    ]);

    $event = $this->get('/.well-known/lan-party.json')->json('organisation.venues.0.events.0');

    expect($event['eventAttendanceMode'])->toBe(4);
    expect($event['eventStatus'])->toBe('https://schema.org/EventScheduled');
    expect($event['sleeping'])->toBe(12);
    expect($event['hasShowers'])->toBeTrue();
    expect($event['foodPolicy'])->toBe(4);
    expect($event['networkConnectionMbps'])->toBe(1000);
});

it('maps visible ticket types with currency, price and availability', function () {
    [$venue, $event] = lppsVenueWithPublishedEvent();

    TicketType::factory()->create([
        'event_id' => $event->id,
        'name' => 'Standard',
        'price' => 1800,
        'quota' => 50,
        'is_hidden' => false,
        'purchase_from' => null,
        'purchase_until' => null,
    ]);
    TicketType::factory()->create([
        'event_id' => $event->id,
        'name' => 'Hidden VIP',
        'is_hidden' => true,
    ]);

    $tickets = $this->get('/.well-known/lan-party.json')->json('organisation.venues.0.events.0.tickets');

    expect($tickets)->toHaveCount(1);
    expect($tickets[0]['name'])->toBe('Standard');
    expect((float) $tickets[0]['price'])->toBe(18.0);
    expect($tickets[0]['priceCurrency'])->toBe(CurrencyResolver::upperCode());
    expect($tickets[0]['availability'])->toBe('https://schema.org/InStock');
});

it('caches the document and invalidates it when an event changes', function () {
    [$venue, $event] = lppsVenueWithPublishedEvent([], ['name' => 'Original Name']);

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.venues.0.events.0.name', 'Original Name');

    $event->update(['name' => 'Renamed Event']);

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.venues.0.events.0.name', 'Renamed Event');
});

it('invalidates the cache when organisation settings change', function () {
    lppsVenueWithPublishedEvent();
    OrganizationSetting::set('name', 'First Name');

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.name', 'First Name');

    OrganizationSetting::set('name', 'Second Name');

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.name', 'Second Name');
});

it('invalidates the cache when a venue address changes', function () {
    [$venue] = lppsVenueWithPublishedEvent(['country_code' => 'GB']);

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.venues.0.countryCode', 'GB');

    $venue->address->update(['country_code' => 'DE']);

    $this->get('/.well-known/lan-party.json')
        ->assertJsonPath('organisation.venues.0.countryCode', 'DE');
});
