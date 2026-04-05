<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Models\User;
use Laravel\Cashier\Cashier;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-002
 * @see docs/mil-std-498/SRS.md SHP-F-003
 * @see docs/mil-std-498/IRS.md IF-STRIPE-001, IF-STRIPE-002
 */
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

        $sessionOptions = [
            'success_url' => route('cart.checkout.success', ['order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cart.checkout.cancel', ['order' => $order->id]),
            'metadata' => [
                'order_id' => $order->id,
            ],
        ];

        if ($order->discount > 0) {
            $stripe = Cashier::stripe();

            $coupon = $stripe->coupons->create([
                'amount_off' => $order->discount,
                'currency' => config('cashier.currency', 'eur'),
                'duration' => 'once',
                'name' => $order->voucher?->code
                    ? "Voucher: {$order->voucher->code}"
                    : 'Order Discount',
            ]);

            $sessionOptions['discounts'] = [['coupon' => $coupon->id]];
        }

        $checkout = $user->checkout($lineItems, $sessionOptions);

        return PaymentResult::redirect($checkout->redirect());
    }

    public function handleSuccess(Order $order, array $parameters = []): bool
    {
        $sessionId = $parameters['session_id'] ?? null;

        if (! $sessionId) {
            return false;
        }

        $stripe = Cashier::stripe();
        $session = $stripe->checkout->sessions->retrieve($sessionId);

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
     * @return array<int, array{price_data: array{currency: string, product_data: array{name: string}, unit_amount: int}, quantity: int}>
     */
    private function buildStripeLineItems(Order $order): array
    {
        return $order->orderLines->map(function (OrderLine $line): array {
            return [
                'price_data' => [
                    'currency' => config('cashier.currency', 'eur'),
                    'product_data' => [
                        'name' => $line->description,
                    ],
                    'unit_amount' => $line->unit_price,
                ],
                'quantity' => $line->quantity,
            ];
        })->all();
    }
}
