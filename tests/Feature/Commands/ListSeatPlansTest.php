<?php

use App\Domain\Seating\Models\SeatPlan;

it('lists all seat plans in a table', function () {
    $seatPlan = SeatPlan::factory()->create(['name' => 'Hall A']);

    $this->artisan('seating:list')
        ->expectsTable(
            ['ID', 'Name', 'Event', 'Blocks'],
            [
                [
                    $seatPlan->id,
                    'Hall A',
                    $seatPlan->event?->name ?? '-',
                    $seatPlan->blocks()->count(),
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters seat plans by event', function () {
    $seatPlan = SeatPlan::factory()->create();
    SeatPlan::factory()->create();

    $this->artisan("seating:list --event={$seatPlan->event_id}")
        ->expectsOutputToContain($seatPlan->name)
        ->assertSuccessful();
});

it('shows message when no seat plans found', function () {
    $this->artisan('seating:list')
        ->expectsOutputToContain('No seat plans found.')
        ->assertSuccessful();
});
