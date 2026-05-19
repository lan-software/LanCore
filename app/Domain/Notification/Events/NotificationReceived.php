<?php

namespace App\Domain\Notification\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires once per database notification, regardless of which notification class
 * produced it — `BroadcastDatabaseNotification` dispatches this from a global
 * listener on `NotificationSent`. The frontend `NotificationBell` consumes this
 * to live-update without any per-notification broadcast wiring.
 *
 * @see docs/mil-std-498/SRS.md NOT-F-020
 */
class NotificationReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $notificationId,
        public readonly string $type,
        public readonly array $data,
        public readonly ?string $createdAt,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'type' => $this->type,
            'data' => $this->data,
            'created_at' => $this->createdAt,
            'read_at' => null,
        ];
    }
}
