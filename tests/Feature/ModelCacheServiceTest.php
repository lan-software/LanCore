<?php

use App\Services\ModelCacheService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->cacheService = app(ModelCacheService::class);
});

it('caches a value and returns it on subsequent calls', function (): void {
    $callCount = 0;

    $first = $this->cacheService->remember('test_group', 'key1', function () use (&$callCount) {
        $callCount++;

        return 'cached_value';
    });

    $second = $this->cacheService->remember('test_group', 'key1', function () use (&$callCount) {
        $callCount++;

        return 'should_not_be_called';
    });

    expect($first)->toBe('cached_value')
        ->and($second)->toBe('cached_value')
        ->and($callCount)->toBe(1);
});

it('caches values with a TTL', function (): void {
    $result = $this->cacheService->remember('test_group', 'ttl_key', fn () => 'ttl_value', 60);

    expect($result)->toBe('ttl_value');

    $cached = $this->cacheService->remember('test_group', 'ttl_key', fn () => 'other', 60);

    expect($cached)->toBe('ttl_value');
});

it('forgets a single key within a group', function (): void {
    $this->cacheService->remember('test_group', 'forget_me', fn () => 'original');
    $this->cacheService->forget('test_group', 'forget_me');

    $result = $this->cacheService->remember('test_group', 'forget_me', fn () => 'refreshed');

    expect($result)->toBe('refreshed');
});

it('flushes all keys in a group', function (): void {
    $this->cacheService->remember('flush_group', 'key_a', fn () => 'value_a');
    $this->cacheService->remember('flush_group', 'key_b', fn () => 'value_b');

    $this->cacheService->flushGroup('flush_group');

    $resultA = $this->cacheService->remember('flush_group', 'key_a', fn () => 'new_a');
    $resultB = $this->cacheService->remember('flush_group', 'key_b', fn () => 'new_b');

    expect($resultA)->toBe('new_a')
        ->and($resultB)->toBe('new_b');
});

it('flushes multiple groups at once', function (): void {
    $this->cacheService->remember('group_x', 'key', fn () => 'x');
    $this->cacheService->remember('group_y', 'key', fn () => 'y');

    $this->cacheService->flushGroup(['group_x', 'group_y']);

    $newX = $this->cacheService->remember('group_x', 'key', fn () => 'new_x');
    $newY = $this->cacheService->remember('group_y', 'key', fn () => 'new_y');

    expect($newX)->toBe('new_x')
        ->and($newY)->toBe('new_y');
});

it('isolates groups so flushing one does not affect another', function (): void {
    $this->cacheService->remember('group_1', 'shared_key', fn () => 'from_group_1');
    $this->cacheService->remember('group_2', 'shared_key', fn () => 'from_group_2');

    $this->cacheService->flushGroup('group_1');

    $group1 = $this->cacheService->remember('group_1', 'shared_key', fn () => 'refreshed');
    $group2 = $this->cacheService->remember('group_2', 'shared_key', fn () => 'should_not_run');

    expect($group1)->toBe('refreshed')
        ->and($group2)->toBe('from_group_2');
});

it('works with non-tag-supporting cache stores', function (): void {
    Config::set('cache.default', 'database');
    Cache::forgetDriver('database');

    $service = new ModelCacheService;

    expect($service->supportsTags())->toBeFalse();

    $service->remember('db_group', 'key', fn () => 'db_cached');

    $cached = $service->remember('db_group', 'key', fn () => 'should_not_run');
    expect($cached)->toBe('db_cached');

    $service->flushGroup('db_group');

    $refreshed = $service->remember('db_group', 'key', fn () => 'refreshed');
    expect($refreshed)->toBe('refreshed');
});

it('recovers from __PHP_Incomplete_Class in cache by evicting and recomputing', function (): void {
    $cacheKey = 'incomplete_group:broken_key';

    // Manually inject a __PHP_Incomplete_Class value into the cache
    $serialized = 'O:22:"__PHP_Incomplete_Class":1:{s:1:"x";i:1;}';
    $incompleteObject = unserialize($serialized);
    Cache::put($cacheKey, $incompleteObject);

    $result = $this->cacheService->remember('incomplete_group', 'broken_key', fn () => 'fresh_value');

    expect($result)->toBe('fresh_value');
});
