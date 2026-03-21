<?php

namespace App\Domain\Shop\Contracts;

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Models\User;

interface PaymentProvider
{
    /**
     * The payment method this provider handles.
     */
    public function method(): PaymentMethod;

    /**
     * Initiate payment for the given order.
     * Returns a PaymentResult indicating whether a redirect is needed.
     */
    public function initiate(User $user, Order $order): PaymentResult;

    /**
     * Handle the payment success callback (if applicable).
     * Returns true if the order was successfully fulfilled.
     */
    public function handleSuccess(Order $order, array $parameters = []): bool;

    /**
     * Handle the payment cancellation callback (if applicable).
     */
    public function handleCancellation(Order $order): void;

    /**
     * Whether this provider requires an external redirect for payment.
     */
    public function requiresRedirect(): bool;
}
