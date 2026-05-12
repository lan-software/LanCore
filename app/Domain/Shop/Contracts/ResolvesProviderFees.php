<?php

namespace App\Domain\Shop\Contracts;

use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Support\ProviderFee;

/**
 * Optional capability implemented by payment providers that can report a fee
 * for a completed order. Implementations should return `null` when the fee
 * isn't available yet (e.g. provider hasn't settled the transaction) so the
 * caller can fall back to an estimate or retry later.
 *
 * Refunds and chargebacks are out of scope for this contract — see GitHub #15
 * follow-ups.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-021
 */
interface ResolvesProviderFees
{
    public function fetchFeeFor(Order $order): ?ProviderFee;
}
