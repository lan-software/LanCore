<?php

use App\Domain\Ticketing\Models\Addon;

it('lists all addons in a table', function () {
    $addon = Addon::factory()->create(['name' => 'Extra Chair', 'price' => 1500]);

    $this->artisan('ticketing:addons:list')
        ->expectsTable(
            ['ID', 'Name', 'Event', 'Price', 'Quota', 'Requires Ticket', 'Hidden'],
            [
                [
                    $addon->id,
                    'Extra Chair',
                    $addon->event?->name ?? '-',
                    '15.00',
                    $addon->quota ?? '∞',
                    $addon->requires_ticket ? 'Yes' : 'No',
                    $addon->is_hidden ? 'Yes' : 'No',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters addons by event', function () {
    $addon = Addon::factory()->create();
    Addon::factory()->create();

    $this->artisan("ticketing:addons:list --event={$addon->event_id}")
        ->expectsOutputToContain($addon->name)
        ->assertSuccessful();
});

it('filters hidden addons', function () {
    Addon::factory()->create(['name' => 'Visible', 'is_hidden' => false]);
    Addon::factory()->create(['name' => 'Hidden', 'is_hidden' => true]);

    $this->artisan('ticketing:addons:list --hidden')
        ->expectsOutputToContain('Hidden')
        ->assertSuccessful();
});

it('shows message when no addons found', function () {
    $this->artisan('ticketing:addons:list')
        ->expectsOutputToContain('No ticket addons found.')
        ->assertSuccessful();
});
