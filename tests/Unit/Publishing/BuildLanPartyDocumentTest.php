<?php

use App\Domain\Event\Enums\AgePolicy;
use App\Domain\Event\Enums\EventSyndicationStatus;
use App\Domain\Event\Enums\SleepingOption;
use App\Domain\Event\Models\Event;
use App\Domain\Publishing\Actions\BuildLanPartyDocument;
use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;

it('composes and decomposes policy bitsets', function () {
    $bits = SleepingOption::toBitset([
        SleepingOption::SharedRooms->value,
        SleepingOption::Camping->value,
    ]);

    expect($bits)->toBe(12);
    expect(SleepingOption::fromBitset(12))->toEqual([SleepingOption::SharedRooms, SleepingOption::Camping]);
    expect(SleepingOption::fromBitset(0))->toBe([]);
});

it('exposes flag options as value/label pairs', function () {
    expect(AgePolicy::options())->toContain(['value' => 8, 'label' => 'Minimum age 18']);
});

it('maps syndication statuses to schema.org URLs', function () {
    expect(EventSyndicationStatus::Scheduled->schemaOrgUrl())->toBe('https://schema.org/EventScheduled');
    expect(EventSyndicationStatus::Cancelled->schemaOrgUrl())->toBe('https://schema.org/EventCancelled');
    expect(EventSyndicationStatus::MovedOnline->schemaOrgUrl())->toBe('https://schema.org/EventMovedOnline');
});

it('builds an organisation rooted document with nested venues and events', function () {
    $address = Address::factory()->create(['country_code' => 'DE']);
    $venue = Venue::factory()->create(['address_id' => $address->id]);
    Event::factory()->published()->create(['venue_id' => $venue->id, 'name' => 'Nested Event']);

    $document = app(BuildLanPartyDocument::class)->execute();

    expect($document['$schema'])->toBe(BuildLanPartyDocument::SCHEMA_URL);
    expect($document['organisation']['apiType'])->toBe('Organisation');
    expect($document['organisation']['venues'])->toHaveCount(1);
    expect($document['organisation']['venues'][0]['events'][0]['name'])->toBe('Nested Event');
});

it('omits venues that only host draft events', function () {
    $venue = Venue::factory()->create();
    Event::factory()->create(['venue_id' => $venue->id]); // draft

    $document = app(BuildLanPartyDocument::class)->execute();

    expect($document['organisation']['venues'])->toBe([]);
});
