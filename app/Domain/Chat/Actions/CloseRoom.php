<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Concerns\AuthorizesModeration;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-023
 *
 * Distinct from consumer-driven `ChatService::writeLock` — this is an
 * explicit moderator action, attributed to the actor and logged as a
 * ModerationAction row.
 */
class CloseRoom
{
    use AuthorizesModeration;

    public function execute(
        User $actor,
        ChatRoom $room,
        ?string $reason = null,
    ): ChatModerationAction {
        $this->ensureCanModerate($actor, $room);

        return DB::transaction(function () use ($actor, $room, $reason): ChatModerationAction {
            if ($room->status === RoomStatus::Open) {
                $room->update([
                    'status' => RoomStatus::WriteLocked,
                    'write_locked_at' => now(),
                ]);
            }

            return ChatModerationAction::create([
                'room_id' => $room->id,
                'actor_id' => $actor->id,
                'target_user_id' => null,
                'action' => ModerationAction::CloseRoom,
                'reason' => $reason,
            ]);
        });
    }
}
