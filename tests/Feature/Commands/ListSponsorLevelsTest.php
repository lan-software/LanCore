<?php

use App\Domain\Sponsoring\Models\SponsorLevel;

it('lists all sponsor levels in a table', function () {
    $level = SponsorLevel::factory()->create(['name' => 'Gold', 'color' => '#FFD700', 'sort_order' => 1]);

    $this->artisan('sponsoring:levels:list')
        ->expectsTable(
            ['ID', 'Name', 'Color', 'Sort', 'Sponsors'],
            [
                [$level->id, 'Gold', '#FFD700', 1, 0],
            ],
        )
        ->assertSuccessful();
});

it('shows message when no sponsor levels found', function () {
    $this->artisan('sponsoring:levels:list')
        ->expectsOutputToContain('No sponsor levels found.')
        ->assertSuccessful();
});
