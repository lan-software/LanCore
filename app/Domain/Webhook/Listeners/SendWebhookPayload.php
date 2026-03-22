<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Webhook\Events\WebhookDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookPayload implements ShouldQueue
{
    public function handle(WebhookDispatched $event): void
    {
        $webhook = $event->webhook;
        $body = json_encode($event->payload);

        if ($body === false) {
            Log::warning('Webhook payload could not be encoded', ['webhook_id' => $webhook->id]);

            return;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $event->event,
        ];

        if ($webhook->secret !== null) {
            $headers['X-Webhook-Signature'] = 'sha256='.hash_hmac('sha256', $body, $webhook->secret);
        }

        Http::withHeaders($headers)->post($webhook->url, $event->payload);
    }
}
