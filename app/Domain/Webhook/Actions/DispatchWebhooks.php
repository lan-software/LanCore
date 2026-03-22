<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Models\Webhook;

class DispatchWebhooks
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(WebhookEvent $event, array $payload): void
    {
        Webhook::query()
            ->active()
            ->forEvent($event)
            ->each(fn (Webhook $webhook) => WebhookDispatched::dispatch($webhook, $payload));
    }
}
