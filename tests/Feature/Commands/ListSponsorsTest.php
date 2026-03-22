<?php

use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;

it('lists all sponsors in a table', function () {
    $sponsor = Sponsor::factory()->create(['name' => 'Acme Corp']);

    $this->artisan('sponsoring:list')
        ->expectsTable(
            ['ID', 'Name', 'Level', 'Link'],
            [
                [
                    $sponsor->id,
                    'Acme Corp',
                    $sponsor->sponsorLevel?->name ?? '-',
                    $sponsor->link ?? '-',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters sponsors by level', function () {
    $level = SponsorLevel::factory()->create();
    $sponsor = Sponsor::factory()->create(['sponsor_level_id' => $level->id]);
    Sponsor::factory()->create();

    $this->artisan("sponsoring:list --level={$level->id}")
        ->expectsOutputToContain($sponsor->name)
        ->assertSuccessful();
});

it('shows message when no sponsors found', function () {
    $this->artisan('sponsoring:list')
        ->expectsOutputToContain('No sponsors found.')
        ->assertSuccessful();
});
