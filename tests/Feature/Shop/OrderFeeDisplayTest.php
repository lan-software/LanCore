<?php

use App\Domain\Shop\Models\Order;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('exposes fee fields on the order show page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $order = Order::factory()->create([
        'fee_amount' => 175,
        'net_amount' => 9825,
        'fee_source' => 'provider',
        'fees_fetched_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get("/backstage/orders/{$order->id}")
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Show')
                ->where('order.fee_amount', 175)
                ->where('order.net_amount', 9825)
                ->where('order.fee_source', 'provider'),
        );
});

it('returns null fee fields when none recorded', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $order = Order::factory()->create([
        'fee_amount' => null,
        'net_amount' => null,
    ]);

    $this->actingAs($admin)
        ->get("/backstage/orders/{$order->id}")
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Show')
                ->where('order.fee_amount', null)
                ->where('order.net_amount', null),
        );
});
