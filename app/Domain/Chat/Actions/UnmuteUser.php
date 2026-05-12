<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Concerns\AuthorizesModeration;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-021
 */
class UnmuteUser
{
    use AuthorizesModeration;

    public function execute(
        User $actor,
        ChatRoom $room,
        User $target,
        ?string $reason = null,
    ): ChatModerationAction {
        $this->ensureCanModerate($actor, $room);

        return DB::transaction(function () use ($actor, $room, $target, $reason): ChatModerationAction {
            ChatRoomMembership::query()
                ->where('room_id', $room->id)
                ->where('user_id', $target->id)
                ->update(['muted_until' => null]);

            return ChatModerationAction::create([
                'room_id' => $room->id,
                'actor_id' => $actor->id,
                'target_user_id' => $target->id,
                'action' => ModerationAction::Unmute,
                'reason' => $reason,
            ]);
        });
    }
}
