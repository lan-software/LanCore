<?php

use App\Domain\Ticketing\Models\TicketType;

it('lists all ticket types in a table', function () {
    $type = TicketType::factory()->create(['name' => 'VIP', 'price' => 5000]);

    $this->artisan('ticketing:types:list')
        ->expectsTable(
            ['ID', 'Name', 'Event', 'Price', 'Quota', 'Sold', 'Locked', 'Hidden'],
            [
                [
                    $type->id,
                    'VIP',
                    $type->event?->name ?? '-',
                    '50.00',
                    $type->quota ?? '∞',
                    0,
                    $type->is_locked ? 'Yes' : 'No',
                    $type->is_hidden ? 'Yes' : 'No',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters ticket types by event', function () {
    $type = TicketType::factory()->create();
    TicketType::factory()->create();

    $this->artisan("ticketing:types:list --event={$type->event_id}")
        ->expectsOutputToContain($type->name)
        ->assertSuccessful();
});

it('filters locked ticket types', function () {
    TicketType::factory()->create(['name' => 'Unlocked', 'is_locked' => false]);
    TicketType::factory()->create(['name' => 'Locked', 'is_locked' => true]);

    $this->artisan('ticketing:types:list --locked')
        ->expectsOutputToContain('Locked')
        ->assertSuccessful();
});

it('filters hidden ticket types', function () {
    TicketType::factory()->create(['name' => 'Visible', 'is_hidden' => false]);
    TicketType::factory()->create(['name' => 'Hidden', 'is_hidden' => true]);

    $this->artisan('ticketing:types:list --hidden')
        ->expectsOutputToContain('Hidden')
        ->assertSuccessful();
});

it('shows message when no ticket types found', function () {
    $this->artisan('ticketing:types:list')
        ->expectsOutputToContain('No ticket types found.')
        ->assertSuccessful();
});
