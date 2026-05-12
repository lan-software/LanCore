<?php

namespace App\Domain\Chat\Concerns;

use App\Domain\Chat\Enums\Permission as ChatPermission;
use App\Domain\Chat\Exceptions\ModerationUnauthorizedException;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Models\User;

/**
 * Shared authorization helper for the four moderation actions. A user may
 * moderate a room if (a) the room's bound policy says so, OR (b) they hold
 * the global `ChatPermission::ModerateChat` permission.
 */
trait AuthorizesModeration
{
    private function ensureCanModerate(User $actor, ChatRoom $room): void
    {
        $resolver = app(PolicyResolver::class);
        $policy = $resolver->resolve($room);

        if ($policy->canModerate($actor, $room)) {
            return;
        }

        if ($actor->hasPermission(ChatPermission::ModerateChat)) {
            return;
        }

        throw new ModerationUnauthorizedException;
    }
}
