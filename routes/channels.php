<?php

use App\Domain\Chat\Broadcasting\ChatRoomChannel;
use App\Domain\Competition\Enums\Permission;
use App\Domain\Event\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Chat presence channel — delegates to ChatRoomChannel for testability.
 * Returns a user descriptor (array) on accept, or false on reject; the
 * descriptor is broadcast as the presence-channel join payload.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-008, CHT-F-036
 * @see docs/mil-std-498/IDD.md §3.14
 */
Broadcast::channel('chat.room.{roomId}', function (User $user, int $roomId): array|false {
    return app(ChatRoomChannel::class)->join($user, $roomId);
});

/**
 * Competition board scheduling channel — only competition managers receive
 * StageScheduleUpdated and RoundScheduleUpdated broadcasts for the given event.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-006
 * @see docs/mil-std-498/SRS.md COMP-RND-004
 */
Broadcast::channel('event.{eventId}.competition-board', function (User $user, string $eventId): bool {
    if (! $user->hasPermission(Permission::ManageCompetitions)) {
        return false;
    }

    return Event::query()->whereKey($eventId)->exists();
});
