<?php

namespace App\Domain\Chat\Broadcasting;

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Models\User;

/**
 * Channel-join authorization for `chat.room.{roomId}` private channels.
 *
 * Archived rooms reject subscription outright; WriteLocked rooms still allow
 * subscription so read-only viewers can see the historical thread. Post
 * authorization is enforced separately by the `PostMessage` action.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-008
 * @see docs/mil-std-498/IDD.md §3.14
 */
class ChatRoomChannel
{
    public function __construct(private readonly PolicyResolver $resolver) {}

    public function join(User $user, int $roomId): bool
    {
        $room = ChatRoom::find($roomId);

        if ($room === null) {
            return false;
        }

        if ($room->status === RoomStatus::Archived) {
            return false;
        }

        return $this->resolver->resolve($room)->canView($user, $room);
    }
}
