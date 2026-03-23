<?php

use App\Domain\Shop\Models\Order;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view orders index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Order::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/orders')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Index')
                ->has('orders.data', 3)
        );
});

it('allows admins to search orders', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->create(['name' => 'John Unique']);
    Order::factory()->for($user)->create();
    Order::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/orders?search=John+Unique')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Index')
                ->has('orders.data', 1)
        );
});

it('allows admins to filter orders by status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Order::factory()->count(2)->create();
    Order::factory()->pending()->create();

    $this->actingAs($admin)
        ->get('/orders?status=pending')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Index')
                ->has('orders.data', 1)
        );
});

it('allows admins to view an order detail', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $order = Order::factory()->create();

    $this->actingAs($admin)
        ->get("/orders/{$order->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('orders/Show')
                ->has('order')
                ->where('order.id', $order->id)
        );
});

it('denies non-admin users from viewing orders index', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/orders')
        ->assertForbidden();
});

it('denies non-admin users from viewing order detail', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $order = Order::factory()->create();

    $this->actingAs($user)
        ->get("/orders/{$order->id}")
        ->assertForbidden();
});
