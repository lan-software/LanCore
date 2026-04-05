<?php

namespace App\Domain\Shop\Listeners;

use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Models\Order;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeCheckoutCompleted
{
    public function __construct(
        private readonly FulfillOrder $fulfillOrder,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'checkout.session.completed') {
            return;
        }

        $session = $event->payload['data']['object'];
        $orderId = $session['metadata']['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $order = Order::find($orderId);

        if (! $order || $order->status !== OrderStatus::Pending) {
            return;
        }

        $order->update([
            'provider_session_id' => $session['id'],
            'provider_transaction_id' => $session['payment_intent'] ?? null,
        ]);

        $this->fulfillOrder->execute($order);
    }
}
