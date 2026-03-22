<?php

namespace App\Domain\Webhook\Events;

use App\Domain\Webhook\Models\Webhook;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookDispatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Webhook $webhook,
        public readonly array $payload,
    ) {}
}
