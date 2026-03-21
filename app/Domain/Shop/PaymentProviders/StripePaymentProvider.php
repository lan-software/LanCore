<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Models\User;
use Stripe\Checkout\Session;

class StripePaymentProvider implements PaymentProvider
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Stripe;
    }

    public function requiresRedirect(): bool
    {
        return true;
    }

    public function initiate(User $user, Order $order): PaymentResult
    {
        $lineItems = $this->buildStripeLineItems($order);

        $checkout = $user->checkout($lineItems, [
            'success_url' => route('cart.checkout.success', ['order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cart.checkout.cancel', ['order' => $order->id]),
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        return PaymentResult::redirect($checkout->redirect());
    }

    public function handleSuccess(Order $order, array $parameters = []): bool
    {
        $sessionId = $parameters['session_id'] ?? null;

        if (! $sessionId) {
            return false;
        }

        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return false;
        }

        $order->update([
            'provider_session_id' => $sessionId,
            'provider_transaction_id' => $session->payment_intent,
        ]);

        return true;
    }

    public function handleCancellation(Order $order): void
    {
        // No Stripe-specific cleanup needed; order status is handled by the caller.
    }

    /**
     * Build Stripe-compatible line items from order lines.
     *
     * @return array<int, array{price_data: array{currency: string, product_data: array{name: string, description: string}, unit_amount: int}, quantity: int}>
     */
    private function buildStripeLineItems(Order $order): array
    {
        return $order->orderLines->map(function (OrderLine $line): array {
            return [
                'price_data' => [
                    'currency' => config('cashier.currency', 'eur'),
                    'product_data' => [
                        'name' => $line->description,
                        'description' => $line->description,
                    ],
                    'unit_amount' => $line->unit_price,
                ],
                'quantity' => $line->quantity,
            ];
        })->all();
    }
}
