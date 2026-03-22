<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Webhook\Events\WebhookDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class SendWebhookPayload implements ShouldQueue
{
    public function handle(WebhookDispatched $event): void
    {
        $webhook = $event->webhook;
        $body = json_encode($event->payload, JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $webhook->event->value,
        ];

        if ($webhook->secret !== null) {
            $headers['X-Webhook-Signature'] = 'sha256='.hash_hmac('sha256', $body, $webhook->secret);
        }

        Http::withHeaders($headers)->timeout(10)->post($webhook->url, $event->payload)->throw();

        $webhook->increment('sent_count');
    }
}
