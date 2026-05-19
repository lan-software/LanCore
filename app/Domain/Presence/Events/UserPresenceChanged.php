<?php

namespace App\Domain\Presence\Events;

use App\Domain\Presence\Enums\PresenceStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires when a user's derived `PresenceStatus` flips (Active ↔ Idle ↔ Offline).
 * Broadcast onto every chat presence channel the user is a member of, so any
 * client rendering that user's indicator can update without a page reload.
 *
 * @see docs/mil-std-498/SRS.md PRS-F-014
 */
class UserPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $roomIds  Chat room ids that should receive the broadcast.
     */
    public function __construct(
        public readonly string $userId,
        public readonly PresenceStatus $status,
        public readonly array $roomIds,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $roomId) => new PresenceChannel('chat.room.'.$roomId),
            $this->roomIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'presence.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status->value,
        ];
    }
}
