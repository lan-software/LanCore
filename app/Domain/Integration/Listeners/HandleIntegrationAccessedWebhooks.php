<?php

namespace App\Domain\Integration\Listeners;

use App\Domain\Integration\Events\IntegrationAccessed;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleIntegrationAccessedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(IntegrationAccessed $event): void
    {
        $app = $event->integrationApp;
        $user = $event->user;

        $this->dispatchWebhooks->execute(WebhookEvent::IntegrationAccessed, [
            'event' => WebhookEvent::IntegrationAccessed->value,
            'integration_app' => [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
