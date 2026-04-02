<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Models\Webhook;

/**
 * @see docs/mil-std-498/SSS.md CAP-WHK-002, CAP-WHK-003
 * @see docs/mil-std-498/SRS.md WHK-F-003, WHK-F-005
 */
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
