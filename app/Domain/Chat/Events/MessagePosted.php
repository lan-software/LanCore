<?php

namespace App\Domain\Chat\Events;

use App\Domain\Chat\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast immediately (not queued) — chat is real-time by design.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-009
 * @see docs/mil-std-498/IDD.md §3.14
 */
class MessagePosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ChatMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.room.'.$this->message->room_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'room_id' => $this->message->room_id,
            'user_id' => $this->message->user_id,
            'body' => $this->message->body,
            'mentions' => $this->message->mentions_json ?? [],
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
