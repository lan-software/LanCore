<?php

namespace App\Services;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

class ModelCacheService
{
    private readonly bool $supportsTags;

    public function __construct()
    {
        $this->supportsTags = Cache::getStore() instanceof TaggableStore;
    }

    /**
     * Cache a value under a named group. Uses cache tags when available,
     * falls back to a key-registry pattern for stores without tag support.
     *
     * If the cached value is a __PHP_Incomplete_Class (due to stale
     * serialisation), the entry is evicted and recomputed transparently.
     */
    public function remember(string $group, string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $cacheKey = "{$group}:{$key}";

        if ($this->supportsTags) {
            $value = $ttl !== null
                ? Cache::tags([$group])->remember($cacheKey, $ttl, $callback)
                : Cache::tags([$group])->rememberForever($cacheKey, $callback);
        } else {
            $this->registerKey($group, $cacheKey);

            $value = $ttl !== null
                ? Cache::remember($cacheKey, $ttl, $callback)
                : Cache::rememberForever($cacheKey, $callback);
        }

        if ($value instanceof \__PHP_Incomplete_Class) {
            $this->forget($group, $key);

            return $callback();
        }

        return $value;
    }

    /**
     * Forget a single cached key within a group.
     */
    public function forget(string $group, string $key): void
    {
        $cacheKey = "{$group}:{$key}";

        if ($this->supportsTags) {
            Cache::tags([$group])->forget($cacheKey);

            return;
        }

        Cache::forget($cacheKey);
        $this->unregisterKey($group, $cacheKey);
    }

    /**
     * Flush all cached values belonging to one or more groups.
     *
     * @param  string|array<int, string>  $groups
     */
    public function flushGroup(string|array $groups): void
    {
        $groups = (array) $groups;

        if ($this->supportsTags) {
            Cache::tags($groups)->flush();

            return;
        }

        foreach ($groups as $group) {
            $registryKey = $this->registryKey($group);

            /** @var array<int, string> $keys */
            $keys = Cache::get($registryKey, []);

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            Cache::forget($registryKey);
        }
    }

    public function supportsTags(): bool
    {
        return $this->supportsTags;
    }

    /**
     * Track a cache key in the group's registry (non-tag stores only).
     */
    private function registerKey(string $group, string $cacheKey): void
    {
        $registryKey = $this->registryKey($group);

        /** @var array<int, string> $keys */
        $keys = Cache::get($registryKey, []);

        if (! in_array($cacheKey, $keys, true)) {
            $keys[] = $cacheKey;
            Cache::forever($registryKey, $keys);
        }
    }

    /**
     * Remove a cache key from the group's registry (non-tag stores only).
     */
    private function unregisterKey(string $group, string $cacheKey): void
    {
        $registryKey = $this->registryKey($group);

        /** @var array<int, string> $keys */
        $keys = Cache::get($registryKey, []);
        $keys = array_values(array_filter($keys, fn (string $k): bool => $k !== $cacheKey));

        if ($keys === []) {
            Cache::forget($registryKey);
        } else {
            Cache::forever($registryKey, $keys);
        }
    }

    private function registryKey(string $group): string
    {
        return "cache_registry:{$group}";
    }
}
