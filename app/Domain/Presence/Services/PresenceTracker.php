<?php

namespace App\Domain\Presence\Services;

use App\Domain\Presence\Enums\PresenceStatus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * @see docs/mil-std-498/SRS.md PRS-F-001..003
 */
class PresenceTracker
{
    /**
     * Record a presence heartbeat for the given user. Writes the current
     * UNIX timestamp under `presence:user:{id}` with a TTL equal to the
     * configured offline threshold so the key self-cleans.
     *
     * Best-effort: Redis failures are logged at debug level and swallowed so
     * a transient Redis outage cannot break a normal page request.
     */
    public function touch(User $user): void
    {
        try {
            $this->connection()->setex(
                $this->keyFor($user->id),
                $this->offlineAfter(),
                (string) now()->getTimestamp(),
            );
        } catch (Throwable $e) {
            Log::debug('[presence] failed to record heartbeat', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Force the user offline by removing their heartbeat key. Called on logout
     * so the user's status flips to Offline immediately rather than waiting
     * for the TTL to expire.
     */
    public function forget(User $user): void
    {
        try {
            $this->connection()->del($this->keyFor($user->id));
        } catch (Throwable $e) {
            Log::debug('[presence] failed to forget heartbeat', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function statusFor(User $user): PresenceStatus
    {
        $raw = null;

        try {
            $raw = $this->connection()->get($this->keyFor($user->id));
        } catch (Throwable $e) {
            Log::debug('[presence] failed to read status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->deriveStatus($raw);
    }

    /**
     * Bulk status read for a list of user IDs. Issues a single MGET so the
     * cost is O(1) round-trips regardless of input size — required for the
     * admin users index (PRS-004).
     *
     * @param  iterable<int>  $userIds
     * @return array<int, PresenceStatus>
     */
    public function bulkStatusFor(iterable $userIds): array
    {
        $ids = [];
        foreach ($userIds as $id) {
            $ids[] = (int) $id;
        }
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        $keys = array_map(fn (int $id): string => $this->keyFor($id), $ids);
        $values = [];

        try {
            $values = $this->connection()->mget($keys);
        } catch (Throwable $e) {
            Log::debug('[presence] failed bulk status read', [
                'count' => count($ids),
                'error' => $e->getMessage(),
            ]);
            $values = array_fill(0, count($ids), null);
        }

        $result = [];
        foreach ($ids as $index => $id) {
            $result[$id] = $this->deriveStatus($values[$index] ?? null);
        }

        return $result;
    }

    private function deriveStatus(mixed $raw): PresenceStatus
    {
        if ($raw === null || $raw === false || $raw === '') {
            return PresenceStatus::Offline;
        }

        $delta = now()->getTimestamp() - (int) $raw;

        return match (true) {
            $delta < $this->idleAfter() => PresenceStatus::Active,
            $delta < $this->offlineAfter() => PresenceStatus::Idle,
            default => PresenceStatus::Offline,
        };
    }

    private function keyFor(int $userId): string
    {
        return 'presence:user:'.$userId;
    }

    private function idleAfter(): int
    {
        return (int) config('presence.idle_after', 300);
    }

    private function offlineAfter(): int
    {
        return (int) config('presence.offline_after', 1800);
    }

    private function connection()
    {
        return Redis::connection(config('presence.cache_store', 'default'));
    }
}
