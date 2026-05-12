<?php

use App\Domain\Chat\Broadcasting\ChatRoomChannel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Chat private channel — delegates to ChatRoomChannel for testability.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-008
 * @see docs/mil-std-498/IDD.md §3.14
 */
Broadcast::channel('chat.room.{roomId}', function (User $user, int $roomId): bool {
    return app(ChatRoomChannel::class)->join($user, $roomId);
});
