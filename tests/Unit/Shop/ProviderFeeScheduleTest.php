<?php

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Support\ProviderFeeSchedule;

it('reads the configured Stripe schedule', function () {
    config([
        'shop.provider_fees.stripe' => [
            'percentage' => 150,
            'fixed_cents' => 25,
            'currency' => 'eur',
            'note' => 'Test note',
        ],
    ]);

    $schedule = ProviderFeeSchedule::fromConfig('stripe');

    expect($schedule->percentageBasisPoints)->toBe(150);
    expect($schedule->fixedCents)->toBe(25);
    expect($schedule->currency)->toBe('eur');
    expect($schedule->note)->toBe('Test note');
});

it('estimates a fee from gross amount', function () {
    $schedule = new ProviderFeeSchedule('stripe', 150, 25, 'eur', null);

    // 1.50% of €100.00 (10000c) = 150c, + 25c fixed = 175c
    expect($schedule->estimateFor(10000))->toBe(175);
});

it('returns zero for non-positive gross', function () {
    $schedule = new ProviderFeeSchedule('stripe', 150, 25, 'eur', null);

    expect($schedule->estimateFor(0))->toBe(0);
    expect($schedule->estimateFor(-100))->toBe(0);
});

it('maps PaymentMethod cases to config keys', function () {
    $stripe = ProviderFeeSchedule::forMethod(PaymentMethod::Stripe);
    $paypal = ProviderFeeSchedule::forMethod(PaymentMethod::PayPal);
    $onSite = ProviderFeeSchedule::forMethod(PaymentMethod::OnSite);

    expect($stripe->providerKey)->toBe('stripe');
    expect($paypal->providerKey)->toBe('paypal');
    expect($onSite->providerKey)->toBe('on_site');
});

it('exposes a display array with decimal percentage', function () {
    $schedule = new ProviderFeeSchedule('stripe', 249, 35, 'eur', 'Foo');

    $arr = $schedule->toDisplayArray();

    expect($arr['percentage'])->toBe(2.49);
    expect($arr['fixed_cents'])->toBe(35);
    expect($arr['currency'])->toBe('eur');
    expect($arr['note'])->toBe('Foo');
});
