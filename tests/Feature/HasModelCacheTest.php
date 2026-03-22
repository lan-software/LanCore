<?php

use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Venue\Models\Venue;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

it('returns cached dropdown options for a model', function (): void {
    $level = SponsorLevel::factory()->create(['name' => 'Gold', 'color' => '#FFD700', 'sort_order' => 1]);

    $options = SponsorLevel::dropdownOptions();

    expect($options)->toHaveCount(1)
        ->and($options->first()->id)->toBe($level->id)
        ->and($options->first()->name)->toBe('Gold')
        ->and($options->first()->color)->toBe('#FFD700');
});

it('serves subsequent calls from cache without re-querying', function (): void {
    SponsorLevel::factory()->create(['sort_order' => 1]);

    $first = SponsorLevel::dropdownOptions();

    // Insert directly via DB to bypass Eloquent events (no cache flush)
    DB::table('sponsor_levels')->insert([
        'name' => 'Sneaky',
        'color' => '#000000',
        'sort_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $second = SponsorLevel::dropdownOptions();

    expect($first)->toHaveCount(1)
        ->and($second)->toHaveCount(1);
});

it('flushes cache when a model is saved', function (): void {
    $level = SponsorLevel::factory()->create(['sort_order' => 1]);
    SponsorLevel::dropdownOptions();

    SponsorLevel::factory()->create(['sort_order' => 2]);
    // The factory create triggers saved event → cache flushed

    $options = SponsorLevel::dropdownOptions();

    expect($options)->toHaveCount(2);
});

it('flushes cache when a model is deleted', function (): void {
    $levelA = SponsorLevel::factory()->create(['sort_order' => 1]);
    $levelB = SponsorLevel::factory()->create(['sort_order' => 2]);
    SponsorLevel::dropdownOptions();

    $levelB->delete();

    $options = SponsorLevel::dropdownOptions();

    expect($options)->toHaveCount(1)
        ->and($options->first()->id)->toBe($levelA->id);
});

it('uses the correct cache group based on table name', function (): void {
    expect(SponsorLevel::cacheGroup())->toBe('sponsor_levels')
        ->and(Venue::cacheGroup())->toBe('venues')
        ->and(Role::cacheGroup())->toBe('roles');
});

it('returns custom dropdown columns per model', function (): void {
    Role::factory()->create(['name' => 'admin', 'label' => 'Administrator']);

    $options = Role::dropdownOptions();

    expect($options->first()->label)->toBe('Administrator');
});

it('serves venue dropdown options from cache', function (): void {
    Venue::factory()->create(['name' => 'Arena']);

    $options = Venue::dropdownOptions();

    expect($options)->toHaveCount(1)
        ->and($options->first()->name)->toBe('Arena');
});
