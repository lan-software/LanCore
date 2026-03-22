<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserRegisteredWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->dispatchWebhooks->execute(WebhookEvent::UserRegistered, [
            'event' => WebhookEvent::UserRegistered->value,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}
