<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;

/**
 * Shared helper for auto-joining users to a Competition-owned chat room.
 * Uses `firstOrCreate` so re-runs are safe and the unique
 * `(room_id, user_id)` constraint is never tripped.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-029
 */
class CompetitionRoomAutoJoin
{
    /**
     * @param  iterable<int>  $userIds
     */
    public function joinUsers(ChatRoom $room, iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            ChatRoomMembership::firstOrCreate(
                ['room_id' => $room->id, 'user_id' => (int) $userId],
                ['joined_at' => now()],
            );
        }
    }
}
