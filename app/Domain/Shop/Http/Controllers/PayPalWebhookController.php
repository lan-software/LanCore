<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-011
 * @see docs/mil-std-498/SRS.md SHP-F-020
 */
class PayPalWebhookController extends Controller
{
    /**
     * @param  Closure(): PayPalClient  $clientFactory
     */
    public function __construct(
        private readonly Closure $clientFactory,
        private readonly FulfillOrder $fulfillOrder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $webhookId = (string) config('paypal.webhook_id', '');

        if ($webhookId === '') {
            Log::warning('PayPal webhook received without PAYPAL_WEBHOOK_ID configured; rejecting.');

            return response()->json(['status' => 'unconfigured'], 503);
        }

        try {
            $client = ($this->clientFactory)();
            $headers = array_map(
                fn (array $v): string => (string) ($v[0] ?? ''),
                $request->headers->all(),
            );
            $verified = $client->verifyWebHookLocally($headers, $webhookId, $rawBody);
        } catch (Throwable $e) {
            Log::warning('PayPal webhook verification threw', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'verification_failed'], 401);
        }

        if (! $verified) {
            return response()->json(['status' => 'verification_failed'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['status' => 'invalid_payload'], 400);
        }

        $eventType = (string) ($payload['event_type'] ?? '');
        $resource = is_array($payload['resource'] ?? null) ? $payload['resource'] : [];

        return match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($resource),
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.REVERSED',
            'CHECKOUT.PAYMENT-APPROVAL.REVERSED' => $this->handleCaptureFailed($resource),
            default => response()->json(['status' => 'ignored', 'event_type' => $eventType]),
        };
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function handleCaptureCompleted(array $resource): JsonResponse
    {
        $order = $this->resolveOrder($resource);

        if (! $order) {
            return response()->json(['status' => 'order_not_found']);
        }

        if ($order->status === OrderStatus::Completed) {
            return response()->json(['status' => 'already_fulfilled']);
        }

        $captureId = (string) ($resource['id'] ?? '');

        if ($captureId !== '') {
            $order->update(['provider_transaction_id' => $captureId]);
        }

        $this->fulfillOrder->execute($order->fresh());

        return response()->json(['status' => 'fulfilled']);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function handleCaptureFailed(array $resource): JsonResponse
    {
        $order = $this->resolveOrder($resource);

        if (! $order) {
            return response()->json(['status' => 'order_not_found']);
        }

        if ($order->status === OrderStatus::Pending) {
            $order->update(['status' => OrderStatus::Failed]);
        }

        return response()->json(['status' => 'marked_failed']);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function resolveOrder(array $resource): ?Order
    {
        $customId = (string) ($resource['custom_id'] ?? '');
        $supplementaryOrderId = (string) ($resource['supplementary_data']['related_ids']['order_id'] ?? '');

        if ($customId !== '' && ctype_digit($customId)) {
            $order = Order::query()->find((int) $customId);

            if ($order && $order->payment_method === PaymentMethod::PayPal) {
                return $order;
            }
        }

        if ($supplementaryOrderId !== '') {
            return Order::query()
                ->where('payment_method', PaymentMethod::PayPal)
                ->where('provider_session_id', $supplementaryOrderId)
                ->first();
        }

        return null;
    }
}
