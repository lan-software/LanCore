<?php

namespace App\Domain\Shop\PaymentProviders;

use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Models\User;

class OnSitePaymentProvider implements PaymentProvider
{
    public function __construct(
        private readonly FulfillOrder $fulfillOrder,
    ) {}

    public function method(): PaymentMethod
    {
        return PaymentMethod::OnSite;
    }

    public function requiresRedirect(): bool
    {
        return false;
    }

    public function initiate(User $user, Order $order): PaymentResult
    {
        // On-site orders are fulfilled immediately — tickets are issued,
        // but payment happens physically at the event.
        $this->fulfillOrder->execute($order);

        return PaymentResult::completed(
            redirect()->route('cart.checkout.success', ['order' => $order->id]),
        );
    }

    public function handleSuccess(Order $order, array $parameters = []): bool
    {
        // Already fulfilled on initiation.
        return true;
    }

    public function handleCancellation(Order $order): void
    {
        // No external provider to clean up.
    }
}
