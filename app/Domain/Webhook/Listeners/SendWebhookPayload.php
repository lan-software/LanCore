<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Models\WebhookDelivery;
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

        $firedAt = now();
        $statusCode = null;
        $succeeded = false;

        try {
            $response = Http::withHeaders($headers)->timeout(10)->post($webhook->url, $event->payload);
            $statusCode = $response->status();
            $succeeded = $response->successful();
            $response->throw();
        } finally {
            WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'status_code' => $statusCode,
                'duration_ms' => (int) $firedAt->diffInMilliseconds(now()),
                'succeeded' => $succeeded,
                'fired_at' => $firedAt,
            ]);
        }

        $webhook->increment('sent_count');
    }
}
