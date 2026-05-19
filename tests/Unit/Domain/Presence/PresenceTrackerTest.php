<?php

use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Config::set('presence.idle_after', 300);
    Config::set('presence.offline_after', 1800);
    Config::set('presence.cache_store', 'default');
});

it('writes a heartbeat with the configured TTL', function (): void {
    $userId = (string) Str::ulid();
    $user = User::factory()->make(['id' => $userId]);

    $connection = Mockery::mock();
    $connection->shouldReceive('setex')
        ->once()
        ->with("presence:user:{$userId}", 1800, Mockery::type('string'));

    Redis::shouldReceive('connection')->with('default')->andReturn($connection);

    (new PresenceTracker)->touch($user);
});

it('returns Active when the heartbeat is fresh', function (): void {
    $userId = (string) Str::ulid();
    $user = User::factory()->make(['id' => $userId]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->once()
        ->with("presence:user:{$userId}")
        ->andReturn((string) now()->subSeconds(30)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Active);
});

it('returns Idle between idle_after and offline_after', function (): void {
    $user = User::factory()->make(['id' => (string) Str::ulid()]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(600)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Idle);
});

it('returns Offline when the key is missing', function (): void {
    $user = User::factory()->make(['id' => (string) Str::ulid()]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')->andReturn(null);

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Offline);
});

it('returns Offline when the heartbeat is older than offline_after', function (): void {
    $user = User::factory()->make(['id' => (string) Str::ulid()]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(3600)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Offline);
});

it('honours overridden thresholds from config', function (): void {
    Config::set('presence.idle_after', 60);
    Config::set('presence.offline_after', 120);

    $user = User::factory()->make(['id' => (string) Str::ulid()]);

    $connection = Mockery::mock();
    $connection->shouldReceive('get')
        ->andReturn((string) now()->subSeconds(90)->getTimestamp());

    Redis::shouldReceive('connection')->andReturn($connection);

    expect((new PresenceTracker)->statusFor($user))->toBe(PresenceStatus::Idle);
});

it('issues a single mget regardless of input size', function (): void {
    $ids = [(string) Str::ulid(), (string) Str::ulid(), (string) Str::ulid()];

    $connection = Mockery::mock();
    $connection->shouldReceive('mget')
        ->once()
        ->with([
            "presence:user:{$ids[0]}",
            "presence:user:{$ids[1]}",
            "presence:user:{$ids[2]}",
        ])
        ->andReturn([
            (string) now()->subSeconds(30)->getTimestamp(),
            (string) now()->subSeconds(600)->getTimestamp(),
            null,
        ]);

    Redis::shouldReceive('connection')->andReturn($connection);

    $result = (new PresenceTracker)->bulkStatusFor($ids);

    expect($result)->toBe([
        $ids[0] => PresenceStatus::Active,
        $ids[1] => PresenceStatus::Idle,
        $ids[2] => PresenceStatus::Offline,
    ]);
});

it('deduplicates user ids before querying', function (): void {
    $idA = (string) Str::ulid();
    $idB = (string) Str::ulid();

    $connection = Mockery::mock();
    $connection->shouldReceive('mget')
        ->once()
        ->with([
            "presence:user:{$idA}",
            "presence:user:{$idB}",
        ])
        ->andReturn([null, null]);

    Redis::shouldReceive('connection')->andReturn($connection);

    $result = (new PresenceTracker)->bulkStatusFor([$idA, $idB, $idA, $idB]);

    expect($result)->toHaveCount(2);
});

it('returns an empty array for an empty input', function (): void {
    Redis::shouldReceive('connection')->never();

    expect((new PresenceTracker)->bulkStatusFor([]))->toBe([]);
});

it('swallows redis failures on touch', function (): void {
    $user = User::factory()->make(['id' => (string) Str::ulid()]);

    $connection = Mockery::mock();
    $connection->shouldReceive('setex')->andThrow(new RuntimeException('redis down'));

    Redis::shouldReceive('connection')->andReturn($connection);

    (new PresenceTracker)->touch($user);

    expect(true)->toBeTrue();
});
