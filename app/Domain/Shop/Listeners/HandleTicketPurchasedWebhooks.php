<?php

namespace App\Domain\Shop\Listeners;

use App\Domain\Shop\Events\TicketPurchased;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @see docs/mil-std-498/SSS.md CAP-WHK-001
 * @see docs/mil-std-498/SRS.md SHP-F-012, WHK-F-005
 */
class HandleTicketPurchasedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(TicketPurchased $event): void
    {
        $order = $event->order;
        $user = $event->user;

        $this->dispatchWebhooks->execute(WebhookEvent::TicketPurchased, [
            'event' => WebhookEvent::TicketPurchased->value,
            'order' => [
                'id' => $order->id,
                'status' => $order->status->value,
                'total' => $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
