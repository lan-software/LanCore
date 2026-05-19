<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Models\ChatRoom;

/**
 * Maps a chat room key onto the user-facing page that *embeds* the chat,
 * rather than the standalone `/chat/rooms/{id}` route. The standalone route
 * exists primarily for moderation deep-links — for ordinary users the chat
 * is always shown in context (the competition page, the match list, etc.),
 * so notifications should land there.
 *
 *   competition:{cid}            → /portal/competitions/{cid}
 *   competition:{cid}:match:{m}  → /portal/competitions/{cid}/matches
 *
 * Returns null for keys we don't know how to consume (future room types).
 * Callers should fall back to `/chat/rooms/{id}` in that case.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-035
 */
class CompetitionChatLinkResolver
{
    public function resolve(ChatRoom $room): ?string
    {
        if (preg_match('/^competition:([^:]+):match:([^:]+)$/', $room->key, $matches)) {
            return "/portal/competitions/{$matches[1]}/matches";
        }

        if (preg_match('/^competition:([^:]+)$/', $room->key, $matches)) {
            return "/portal/competitions/{$matches[1]}";
        }

        return null;
    }
}
