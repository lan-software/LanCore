<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-007, CHT-F-008
 */
class ChatService
{
    /**
     * Idempotent room creation. Subsequent calls with the same key return the
     * existing row without touching its state — consumers can call this on
     * every relevant lifecycle event without worrying about double-creation.
     *
     * @param  array{title?: string|null, consumer_domain?: string, created_by?: int|null}  $attributes
     */
    public function ensureRoom(string $key, RoomPolicy $policy, array $attributes = []): ChatRoom
    {
        return ChatRoom::firstOrCreate(
            ['key' => $key],
            array_merge([
                'consumer_domain' => $attributes['consumer_domain'] ?? 'Unknown',
                'policy_class' => $policy::class,
                'status' => RoomStatus::Open,
                'opened_at' => now(),
                'title' => $attributes['title'] ?? null,
                'created_by' => $attributes['created_by'] ?? null,
            ], $attributes),
        );
    }

    public function writeLock(ChatRoom $room): void
    {
        if ($room->status === RoomStatus::Archived) {
            return;
        }

        if ($room->status === RoomStatus::WriteLocked) {
            return;
        }

        $room->update([
            'status' => RoomStatus::WriteLocked,
            'write_locked_at' => now(),
        ]);
    }

    public function archive(ChatRoom $room): void
    {
        if ($room->status === RoomStatus::Archived) {
            return;
        }

        $room->update([
            'status' => RoomStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}
