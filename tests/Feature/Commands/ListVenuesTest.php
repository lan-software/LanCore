<?php

use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;

it('lists all venues in a table', function () {
    $venue = Venue::factory()->create(['name' => 'Convention Center']);

    $this->artisan('venue:list')
        ->expectsTable(
            ['ID', 'Name', 'City', 'Country'],
            [
                [
                    $venue->id,
                    'Convention Center',
                    $venue->address?->city ?? '-',
                    $venue->address?->country ?? '-',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters venues by city', function () {
    $address = Address::factory()->create(['city' => 'Berlin']);
    $venue = Venue::factory()->create(['address_id' => $address->id]);
    Venue::factory()->create();

    $this->artisan('venue:list --city=Berlin')
        ->expectsOutputToContain($venue->name)
        ->assertSuccessful();
});

it('shows message when no venues found', function () {
    $this->artisan('venue:list')
        ->expectsOutputToContain('No venues found.')
        ->assertSuccessful();
});
