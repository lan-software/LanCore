<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-011
 * @see docs/mil-std-498/SRS.md SHP-F-019, SHP-F-020
 * @see docs/mil-std-498/IRS.md IF-PAYPAL-001, IF-PAYPAL-002, IF-PAYPAL-003
 */
class PayPalPaymentProvider implements PaymentProvider
{
    /**
     * @param  Closure(): PayPalClient  $clientFactory  Resolver closure — returns a fresh PayPalClient per call. Octane-safe: no container/config state cached in this provider.
     */
    public function __construct(private readonly Closure $clientFactory) {}

    public function method(): PaymentMethod
    {
        return PaymentMethod::PayPal;
    }

    public function requiresRedirect(): bool
    {
        return true;
    }

    public function initiate(User $user, Order $order): PaymentResult
    {
        $client = ($this->clientFactory)();
        $currency = strtoupper((string) ($order->currency ?: 'eur'));
        $client->setCurrency($currency);

        $client->setBrandName((string) config('app.name'));
        $client->setReturnAndCancelUrl(
            route('cart.checkout.success', ['order' => $order->id]),
            route('cart.checkout.cancel', ['order' => $order->id]),
        );

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'invoice_id' => $order->invoice_number ?? (string) $order->id,
                'custom_id' => (string) $order->id,
                'description' => 'Order #'.$order->id,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($order->total / 100, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => $currency,
                            'value' => number_format(($order->subtotal) / 100, 2, '.', ''),
                        ],
                        'discount' => [
                            'currency_code' => $currency,
                            'value' => number_format($order->discount / 100, 2, '.', ''),
                        ],
                    ],
                ],
                'items' => $this->buildItems($order, $currency),
            ]],
        ];

        $response = $client->createOrderWithPaymentSource($payload);

        if (! is_array($response) || ! isset($response['id'])) {
            throw new RuntimeException('PayPal createOrder did not return an order id: '.json_encode($response));
        }

        $order->update(['provider_session_id' => (string) $response['id']]);

        $approvalHref = $this->extractApprovalLink($response);

        if ($approvalHref === null) {
            throw new RuntimeException('PayPal createOrder response is missing the payer-action/approve link.');
        }

        return PaymentResult::redirect(redirect()->away($approvalHref));
    }

    public function handleSuccess(Order $order, array $parameters = []): bool
    {
        // PayPal appends `?token=<PayPal-Order-ID>&PayerID=<payer>` to the
        // return URL; fall back to the id we persisted at createOrder time.
        $orderId = (string) ($parameters['token'] ?? $parameters['paypal_order'] ?? $order->provider_session_id ?? '');

        if ($orderId === '') {
            return false;
        }

        $client = ($this->clientFactory)();
        $capture = $client->capturePaymentOrder($orderId);

        if (! is_array($capture)) {
            Log::warning('PayPal capturePaymentOrder returned a non-array response', [
                'order_id' => $order->id,
                'paypal_order' => $orderId,
            ]);

            return false;
        }

        $status = $capture['status'] ?? null;
        $captureId = $client->getCaptureIdFromOrder($capture);

        if ($status !== 'COMPLETED' || $captureId === null) {
            return false;
        }

        $order->update([
            'provider_session_id' => $orderId,
            'provider_transaction_id' => $captureId,
        ]);

        return true;
    }

    public function handleCancellation(Order $order): void {}

    /**
     * @return array<int, array{name: string, quantity: string, unit_amount: array{currency_code: string, value: string}}>
     */
    private function buildItems(Order $order, string $currency): array
    {
        return $order->orderLines->map(fn (OrderLine $line): array => [
            'name' => mb_substr((string) $line->description, 0, 127),
            'quantity' => (string) $line->quantity,
            'unit_amount' => [
                'currency_code' => $currency,
                'value' => number_format($line->unit_price / 100, 2, '.', ''),
            ],
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractApprovalLink(array $response): ?string
    {
        foreach ((array) ($response['links'] ?? []) as $link) {
            if (! is_array($link)) {
                continue;
            }

            $rel = (string) ($link['rel'] ?? '');

            if ($rel === 'payer-action' || $rel === 'approve') {
                return (string) ($link['href'] ?? '') ?: null;
            }
        }

        return null;
    }
}
