<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Contracts\ResolvesProviderFees;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Shop\Support\ProviderFee;

/**
 * Fetches the actual provider fee for an order and persists the snapshot:
 * `fee_amount` + `net_amount` (= total − fee) + `fee_source` + `fees_fetched_at`.
 *
 * Returns the persisted fee, or null when the provider can't (yet) report
 * one. Callers — webhook listeners, backfill command — treat null as
 * "try again later".
 *
 * @see docs/mil-std-498/SRS.md SHP-F-021
 */
class PersistProviderFee
{
    public function __construct(private readonly PaymentProviderManager $providers) {}

    public function execute(Order $order): ?ProviderFee
    {
        $provider = $this->providers->resolve($order->payment_method);
        if (! $provider instanceof ResolvesProviderFees) {
            return null;
        }

        $fee = $provider->fetchFeeFor($order);
        if ($fee === null) {
            return null;
        }

        $order->forceFill([
            'fee_amount' => $fee->feeCents,
            'net_amount' => max(0, ((int) $order->total) - $fee->feeCents),
            'fee_source' => $fee->source,
            'fees_fetched_at' => now(),
        ])->save();

        return $fee;
    }
}
