<?php

/**
 * @see docs/mil-std-498/SRS.md SHP-F-017
 */

use App\Domain\Shop\Models\Order;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('shows the user their own orders', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    Order::factory()->count(3)->for($user)->create();
    Order::factory()->count(2)->create(); // other user's orders

    $this->actingAs($user)
        ->get('/my-orders')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('my-orders/Index')
                ->has('orders', 3)
        );
});

it('does not show other users orders', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    Order::factory()->count(2)->create(); // other user's orders

    $this->actingAs($user)
        ->get('/my-orders')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('my-orders/Index')
                ->has('orders', 0)
        );
});

it('shows order detail for own order', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAs($user)
        ->get("/my-orders/{$order->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('my-orders/Show')
                ->where('order.id', $order->id)
        );
});

it('denies viewing another users order', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $order = Order::factory()->create(); // another user's order

    $this->actingAs($user)
        ->get("/my-orders/{$order->id}")
        ->assertForbidden();
});

it('requires authentication', function () {
    $this->get('/my-orders')
        ->assertRedirect('/login');
});
