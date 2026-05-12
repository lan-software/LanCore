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
 * Reopens a write-locked or archived chat room. Logged as a moderation action
 * so the audit trail mirrors `CloseRoom`. Archived rooms reopen back to Open;
 * write-locked rooms also reopen to Open. No-op if the room is already Open.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-033
 */
class ReopenRoom
{
    use AuthorizesModeration;

    public function execute(
        User $actor,
        ChatRoom $room,
        ?string $reason = null,
    ): ChatModerationAction {
        $this->ensureCanModerate($actor, $room);

        return DB::transaction(function () use ($actor, $room, $reason): ChatModerationAction {
            if ($room->status !== RoomStatus::Open) {
                $room->update([
                    'status' => RoomStatus::Open,
                    'write_locked_at' => null,
                    'archived_at' => null,
                ]);
            }

            return ChatModerationAction::create([
                'room_id' => $room->id,
                'actor_id' => $actor->id,
                'target_user_id' => null,
                'action' => ModerationAction::ReopenRoom,
                'reason' => $reason,
            ]);
        });
    }
}
