<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleProfileUpdatedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(ProfileUpdated $event): void
    {
        $user = $event->user;

        $this->dispatchWebhooks->execute(WebhookEvent::ProfileUpdated, [
            'event' => WebhookEvent::ProfileUpdated->value,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
