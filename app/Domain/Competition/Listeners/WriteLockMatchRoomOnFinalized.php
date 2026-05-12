<?php

namespace App\Domain\Competition\Listeners;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Events\MatchFinalized;

/**
 * Write-locks the per-match chat room when the match is finalized. Reads
 * are preserved so participants can still scroll history.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-032
 */
class WriteLockMatchRoomOnFinalized
{
    public function __construct(private readonly ChatService $chat) {}

    public function handle(MatchFinalized $event): void
    {
        $room = ChatRoom::query()
            ->where('key', "competition:{$event->competition->id}:match:{$event->lanbracketsMatchId}")
            ->first();

        if ($room === null) {
            return;
        }

        $this->chat->writeLock($room);
    }
}
