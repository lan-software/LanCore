<?php

use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

beforeEach(function (): void {
    Config::set('presence.idle_after', 300);
    Config::set('presence.offline_after', 1800);
    Config::set('presence.cache_store', 'default');
});

it('writes a heartbeat with the configured TTL', function (): void {
    $user = User::factory()->make(['id' => 42]);

    $connection = Mockery::mock();
    $connection->shouldReceive('setex')
        ->once()
        ->with('presence:user:42', 1800, Mockery::type('string'));

    Redis::shouldReceive('connection')->with('default')->andReturn($connection);

    (new PresenceTracker)->touch($user);
});

it('returns Active when the heartbeat is fresh', function (): void {
    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->once()
        ->with('presence:user:1')
        ->andReturn((string) now()->subSeconds(30)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Active);
});

it('returns Idle between idle_after and offline_after', function (): void {
    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(600)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Idle);
});

it('returns Offline when the key is missing', function (): void {
    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')->andReturn(null);

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Offline);
});

it('returns Offline when the heartbeat is older than offline_after', function (): void {
    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(3600)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Offline);
});

it('honours overridden thresholds from config', function (): void {
    Config::set('presence.idle_after', 60);
    Config::set('presence.offline_after', 120);

    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(90)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Idle);
});

it('issues a single mget regardless of input size', function (): void {
    $connection = Mockery::mock();
    $connection->shouldReceive('mget')
        ->once()
        ->with([
            'presence:user:1',
            'presence:user:2',
            'presence:user:3',
        ])
        ->andReturn([
            (string) now()->subSeconds(30)->getTimestamp(),
            (string) now()->subSeconds(600)->getTimestamp(),
            null,
        ]);

    Redis::shouldReceive('connection')->andReturn($connection);

    $result = (new PresenceTracker)->bulkStatusFor([1, 2, 3]);

    expect($result)->toBe([
        1 => PresenceStatus::Active,
        2 => PresenceStatus::Idle,
        3 => PresenceStatus::Offline,
    ]);
});

it('deduplicates user ids before querying', function (): void {
    $connection = Mockery::mock();
    $connection->shouldReceive('mget')
        ->once()
        ->with([
            'presence:user:1',
            'presence:user:2',
        ])
        ->andReturn([null, null]);

    Redis::shouldReceive('connection')->andReturn($connection);

    $result = (new PresenceTracker)->bulkStatusFor([1, 2, 1, 2]);

    expect($result)->toHaveCount(2);
});

it('returns an empty array for an empty input', function (): void {
    Redis::shouldReceive('connection')->never();

    expect((new PresenceTracker)->bulkStatusFor([]))->toBe([]);
});

it('swallows redis failures on touch', function (): void {
    $user = User::factory()->make(['id' => 1]);

    $connection = Mockery::mock();
    $connection->shouldReceive('setex')->andThrow(new RuntimeException('redis down'));

    Redis::shouldReceive('connection')->andReturn($connection);

    (new PresenceTracker)->touch($user);

    expect(true)->toBeTrue();
});
