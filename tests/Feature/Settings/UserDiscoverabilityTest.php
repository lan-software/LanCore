<?php

use App\Models\User;

test('user is discoverable by anyone when is_ticket_discoverable is true', function () {
    $target = User::factory()->create(['is_ticket_discoverable' => true]);
    $searcher = User::factory()->create();

    expect($target->isDiscoverableBy($searcher))->toBeTrue();
});

test('user is not discoverable when is_ticket_discoverable is false and searcher not in allowlist', function () {
    $target = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => ['alice'],
    ]);
    $searcher = User::factory()->create(['name' => 'bob']);

    expect($target->isDiscoverableBy($searcher))->toBeFalse();
});

test('user is discoverable by allowlisted users', function () {
    $searcher = User::factory()->create(['name' => 'alice']);
    $target = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => ['alice'],
    ]);

    expect($target->isDiscoverableBy($searcher))->toBeTrue();
});

test('user with empty allowlist and not discoverable is not found by anyone', function () {
    $target = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => [],
    ]);
    $searcher = User::factory()->create();

    expect($target->isDiscoverableBy($searcher))->toBeFalse();
});
