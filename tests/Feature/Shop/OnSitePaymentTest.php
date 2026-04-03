<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-003
 * @see docs/mil-std-498/SRS.md SHP-F-004
 */

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('creates an on-site order that stays pending', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->onSite()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'subtotal' => 2500,
        'total' => 2500,
        'metadata' => json_encode([
            ['ticket_type_id' => $ticketType->id, 'quantity' => 1, 'addon_ids' => []],
        ]),
    ]);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::OnSite);
    expect($order->tickets)->toHaveCount(0);
});

it('shows pending message on checkout success for on-site orders', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $order = Order::factory()->pending()->onSite()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/success")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('shop/CheckoutSuccess')
                ->where('order.status', 'pending')
        );
});

it('allows admin to confirm payment on a pending on-site order', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->onSite()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'subtotal' => 2500,
        'total' => 2500,
        'metadata' => json_encode([
            ['ticket_type_id' => $ticketType->id, 'quantity' => 1, 'addon_ids' => []],
        ]),
    ]);

    $order->orderLines()->create([
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'description' => $ticketType->name,
        'quantity' => 1,
        'unit_price' => 2500,
        'total_price' => 2500,
    ]);

    $this->actingAs($admin)
        ->patch("/orders/{$order->id}/confirm-payment")
        ->assertRedirect();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->tickets)->toHaveCount(1);
});

it('prevents non-admin from confirming payment', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $order = Order::factory()->pending()->onSite()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->patch("/orders/{$order->id}/confirm-payment")
        ->assertForbidden();
});

it('prevents confirming payment on non-on-site orders', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $order = Order::factory()->pending()->create([
        'payment_method' => PaymentMethod::Stripe,
    ]);

    $this->actingAs($admin)
        ->patch("/orders/{$order->id}/confirm-payment")
        ->assertRedirect()
        ->assertSessionHasErrors('order');
});

it('prevents confirming payment on already completed orders', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $order = Order::factory()->onSite()->create([
        'status' => OrderStatus::Completed,
    ]);

    $this->actingAs($admin)
        ->patch("/orders/{$order->id}/confirm-payment")
        ->assertRedirect()
        ->assertSessionHasErrors('order');
});
