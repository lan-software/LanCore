<?php

namespace App\Domain\Event\Listeners;

use App\Domain\Event\Events\EventPublished;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleEventPublishedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(EventPublished $event): void
    {
        $lanEvent = $event->event;

        $this->dispatchWebhooks->execute(WebhookEvent::EventPublished, [
            'event' => WebhookEvent::EventPublished->value,
            'lan_event' => [
                'id' => $lanEvent->id,
                'name' => $lanEvent->name,
                'start_date' => $lanEvent->start_date?->toIso8601String(),
                'end_date' => $lanEvent->end_date?->toIso8601String(),
            ],
        ]);
    }
}
