<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Concerns\AuthorizesModeration;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-022
 */
class DeleteMessage
{
    use AuthorizesModeration;

    public function execute(
        User $actor,
        ChatMessage $message,
        ?string $reason = null,
    ): ChatModerationAction {
        $this->ensureCanModerate($actor, $message->room);

        return DB::transaction(function () use ($actor, $message, $reason): ChatModerationAction {
            $message->update(['deleted_by' => $actor->id]);
            $message->delete();

            return ChatModerationAction::create([
                'room_id' => $message->room_id,
                'actor_id' => $actor->id,
                'target_user_id' => $message->user_id,
                'action' => ModerationAction::DeleteMessage,
                'reason' => $reason,
            ]);
        });
    }
}
