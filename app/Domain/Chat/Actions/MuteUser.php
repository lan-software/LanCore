<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Concerns\AuthorizesModeration;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-020
 */
class MuteUser
{
    use AuthorizesModeration;

    public function execute(
        User $actor,
        ChatRoom $room,
        User $target,
        CarbonInterface $until,
        ?string $reason = null,
    ): ChatModerationAction {
        $this->ensureCanModerate($actor, $room);

        return DB::transaction(function () use ($actor, $room, $target, $until, $reason): ChatModerationAction {
            $membership = ChatRoomMembership::firstOrCreate(
                ['room_id' => $room->id, 'user_id' => $target->id],
                ['joined_at' => now()],
            );

            $membership->update(['muted_until' => $until]);

            return ChatModerationAction::create([
                'room_id' => $room->id,
                'actor_id' => $actor->id,
                'target_user_id' => $target->id,
                'action' => ModerationAction::Mute,
                'reason' => $reason,
                'expires_at' => $until,
            ]);
        });
    }
}
