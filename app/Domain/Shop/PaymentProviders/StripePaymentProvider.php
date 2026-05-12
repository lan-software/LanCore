<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Contracts\ResolvesProviderFees;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Domain\Shop\Support\ProviderFee;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Throwable;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-002
 * @see docs/mil-std-498/SRS.md SHP-F-003
 * @see docs/mil-std-498/IRS.md IF-STRIPE-001, IF-STRIPE-002
 */
class StripePaymentProvider implements PaymentProvider, ResolvesProviderFees
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
                'currency' => $order->currency ?: 'eur',
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
     * Resolve the actual fee Stripe charged for this order's PaymentIntent.
     * Retrieves the PI with `latest_charge.balance_transaction` expanded — the
     * balance_transaction holds the settled fee in the smallest currency unit.
     * Returns null when the order has no transaction id, when Stripe hasn't
     * surfaced a balance transaction yet, or on any API error.
     */
    public function fetchFeeFor(Order $order): ?ProviderFee
    {
        $paymentIntentId = $order->provider_transaction_id;
        if (! is_string($paymentIntentId) || $paymentIntentId === '') {
            return null;
        }

        try {
            $stripe = Cashier::stripe();
            $intent = $stripe->paymentIntents->retrieve(
                $paymentIntentId,
                ['expand' => ['latest_charge.balance_transaction']],
            );
        } catch (Throwable $e) {
            Log::warning('Stripe fee fetch failed', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $charge = $intent->latest_charge ?? null;
        if (! is_object($charge)) {
            return null;
        }

        $balanceTransaction = $charge->balance_transaction ?? null;
        if (! is_object($balanceTransaction) || ! isset($balanceTransaction->fee, $balanceTransaction->currency)) {
            return null;
        }

        return ProviderFee::provider(
            feeCents: (int) $balanceTransaction->fee,
            currency: (string) $balanceTransaction->currency,
        );
    }

    /**
     * Build Stripe-compatible line items from order lines.
     *
     * @return array<int, array{price_data: array{currency: string, product_data: array{name: string}, unit_amount: int}, quantity: int}>
     */
    private function buildStripeLineItems(Order $order): array
    {
        $currency = $order->currency ?: 'eur';

        return $order->orderLines->map(fn (OrderLine $line): array => [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $line->description,
                ],
                'unit_amount' => $line->unit_price,
            ],
            'quantity' => $line->quantity,
        ])->all();
    }
}
