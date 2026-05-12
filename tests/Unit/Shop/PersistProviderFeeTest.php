<?php

use App\Domain\Shop\Actions\PersistProviderFee;
use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Contracts\ResolvesProviderFees;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Shop\Support\ProviderFee;
use App\Models\User;

function fakeProviderWithFee(?ProviderFee $fee): PaymentProvider
{
    return new class($fee) implements PaymentProvider, ResolvesProviderFees
    {
        public function __construct(private readonly ?ProviderFee $fee) {}

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
            return PaymentResult::completed(redirect('/'));
        }

        public function handleSuccess(Order $order, array $parameters = []): bool
        {
            return true;
        }

        public function handleCancellation(Order $order): void {}

        public function fetchFeeFor(Order $order): ?ProviderFee
        {
            return $this->fee;
        }
    };
}

it('persists a fee on the order', function () {
    $manager = new PaymentProviderManager;
    $manager->register(fakeProviderWithFee(ProviderFee::provider(175, 'eur')));

    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::Stripe,
        'total' => 10000,
    ]);

    $action = new PersistProviderFee($manager);
    $result = $action->execute($order);

    expect($result)->not->toBeNull();
    $order->refresh();
    expect($order->fee_amount)->toBe(175);
    expect($order->net_amount)->toBe(9825);
    expect($order->fee_source)->toBe('provider');
    expect($order->fees_fetched_at)->not->toBeNull();
});

it('returns null and writes nothing when the provider has no fee yet', function () {
    $manager = new PaymentProviderManager;
    $manager->register(fakeProviderWithFee(null));

    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::Stripe,
        'total' => 10000,
    ]);

    $action = new PersistProviderFee($manager);
    $result = $action->execute($order);

    expect($result)->toBeNull();
    $order->refresh();
    expect($order->fee_amount)->toBeNull();
    expect($order->net_amount)->toBeNull();
    expect($order->fees_fetched_at)->toBeNull();
});

it('clamps net_amount to zero when the fee exceeds the total', function () {
    $manager = new PaymentProviderManager;
    $manager->register(fakeProviderWithFee(ProviderFee::provider(20000, 'eur')));

    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::Stripe,
        'total' => 100,
    ]);

    (new PersistProviderFee($manager))->execute($order);

    $order->refresh();
    expect($order->net_amount)->toBe(0);
});
