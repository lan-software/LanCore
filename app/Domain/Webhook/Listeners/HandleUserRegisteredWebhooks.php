<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Models\Webhook;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserRegisteredWebhooks implements ShouldQueue
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $payload = [
            'event' => WebhookEvent::UserRegistered->value,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ];

        Webhook::query()
            ->active()
            ->forEvent(WebhookEvent::UserRegistered)
            ->each(function (Webhook $webhook) use ($payload): void {
                WebhookDispatched::dispatch($webhook, WebhookEvent::UserRegistered->value, $payload);
            });
    }
}
