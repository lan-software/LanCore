<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\Permission as ChatPermission;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Enums\Permission as CompetitionPermission;
use App\Models\User;

/**
 * RoomPolicy for per-match chat rooms (key: `competition:{cid}:match:{mid}`).
 *
 * canView is the canonical signal that you are on the match — the cheapest
 * source of truth is the membership table populated by the auto-join. We do
 * not try to re-derive participation from the LanBrackets snapshot at every
 * authorization, because that's an external service and a chat room may
 * outlive a participant's slot reassignment.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-030
 */
class MatchRoomPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        return ChatRoomMembership::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return $this->canView($user, $room) && $room->status === RoomStatus::Open;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return $user->hasPermission(CompetitionPermission::ManageCompetitions)
            || $user->hasPermission(ChatPermission::ModerateChat);
    }

    public function describe(ChatRoom $room): string
    {
        return $room->title ?? 'Match chat';
    }
}
