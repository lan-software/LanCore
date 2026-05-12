<?php

use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;

it('only touches completed orders without a fees_fetched_at', function () {
    Order::factory()->create([
        'status' => OrderStatus::Completed,
        'payment_method' => PaymentMethod::OnSite,
        'total' => 5000,
    ]);
    $alreadyDone = Order::factory()->create([
        'status' => OrderStatus::Completed,
        'payment_method' => PaymentMethod::OnSite,
        'total' => 5000,
        'fee_amount' => 0,
        'fees_fetched_at' => now(),
    ]);
    $pending = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_method' => PaymentMethod::OnSite,
        'total' => 5000,
    ]);

    $this->artisan('shop:backfill-fees')
        ->assertSuccessful();

    expect($alreadyDone->fresh()->fees_fetched_at->equalTo($alreadyDone->fees_fetched_at))->toBeTrue();
    expect($pending->fresh()->fees_fetched_at)->toBeNull();
});

it('updates eligible on_site orders with a zero fee', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Completed,
        'payment_method' => PaymentMethod::OnSite,
        'total' => 5000,
    ]);

    $this->artisan('shop:backfill-fees', ['--provider' => 'on_site'])
        ->assertSuccessful();

    $order->refresh();
    expect($order->fee_amount)->toBe(0);
    expect($order->net_amount)->toBe(5000);
    expect($order->fee_source)->toBe('provider');
});

it('rejects unknown providers', function () {
    $this->artisan('shop:backfill-fees', ['--provider' => 'nope'])
        ->assertFailed();
});

it('dry-run does not persist changes', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Completed,
        'payment_method' => PaymentMethod::OnSite,
        'total' => 5000,
    ]);

    $this->artisan('shop:backfill-fees', ['--dry-run' => true])
        ->assertSuccessful();

    expect($order->fresh()->fees_fetched_at)->toBeNull();
});
