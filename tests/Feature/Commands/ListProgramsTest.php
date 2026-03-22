<?php

use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Program\Models\Program;

it('lists all programs in a table', function () {
    $program = Program::factory()->create(['name' => 'Main Stage', 'visibility' => ProgramVisibility::Public]);

    $this->artisan('programs:list')
        ->expectsTable(
            ['ID', 'Name', 'Visibility', 'Event', 'Time Slots', 'Sort'],
            [
                [
                    $program->id,
                    'Main Stage',
                    'public',
                    $program->event?->name ?? '-',
                    0,
                    $program->sort_order,
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters programs by visibility', function () {
    Program::factory()->create(['visibility' => ProgramVisibility::Private]);
    $public = Program::factory()->create(['visibility' => ProgramVisibility::Public]);

    $this->artisan('programs:list --visibility=public')
        ->expectsOutputToContain($public->name)
        ->assertSuccessful();
});

it('shows error for invalid visibility', function () {
    $this->artisan('programs:list --visibility=invalid')
        ->expectsOutputToContain("Invalid visibility 'invalid'")
        ->assertFailed();
});

it('filters programs by event', function () {
    $program = Program::factory()->create();
    Program::factory()->create();

    $this->artisan("programs:list --event={$program->event_id}")
        ->expectsOutputToContain($program->name)
        ->assertSuccessful();
});

it('shows message when no programs found', function () {
    $this->artisan('programs:list')
        ->expectsOutputToContain('No programs found.')
        ->assertSuccessful();
});
