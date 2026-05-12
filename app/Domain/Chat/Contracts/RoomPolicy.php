<?php

namespace App\Domain\Chat\Contracts;

use App\Domain\Chat\Models\ChatRoom;
use App\Models\User;

/**
 * The single coupling point between the Chat CSCI and any consuming domain.
 * Each consumer (Competition, future others) ships its own implementation
 * and binds it to the room via `ChatService::ensureRoom`. Chat itself never
 * reaches into consumer domain models.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-007, CHT-F-008
 */
interface RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool;

    public function canPost(User $user, ChatRoom $room): bool;

    public function canModerate(User $user, ChatRoom $room): bool;

    /**
     * Human-readable description for the room ("Competition X" / "Match Y") —
     * used in admin/audit surfaces only, never shown to participants.
     */
    public function describe(ChatRoom $room): string;
}
