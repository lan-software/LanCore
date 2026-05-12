<?php

namespace App\Domain\Chat\Broadcasting;

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Models\User;

/**
 * Channel-join authorization for `chat.room.{roomId}` *presence* channels.
 *
 * Archived rooms reject subscription outright; WriteLocked rooms still allow
 * subscription so read-only viewers can see the historical thread. Post
 * authorization is enforced separately by the `PostMessage` action.
 *
 * Returns `false` to deny, or a small user descriptor to admit + advertise
 * the user to other subscribers (presence-channel semantics). The descriptor
 * is the payload other clients see in `.here()` / `.joining()` events and
 * drives the "active in chat" checkmark overlay on the presence indicator.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-008, CHT-F-036
 * @see docs/mil-std-498/IDD.md §3.14
 */
class ChatRoomChannel
{
    public function __construct(private readonly PolicyResolver $resolver) {}

    /**
     * @return array{id: int, name: ?string, username: ?string}|false
     */
    public function join(User $user, int $roomId): array|false
    {
        $room = ChatRoom::find($roomId);

        if ($room === null) {
            return false;
        }

        if ($room->status === RoomStatus::Archived) {
            return false;
        }

        if (! $this->resolver->resolve($room)->canView($user, $room)) {
            return false;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
        ];
    }
}
