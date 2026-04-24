<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-011
 * @see docs/mil-std-498/SRS.md SHP-F-019
 */

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CartItem;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\ShopSetting;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    ShopSetting::set('enabled_payment_methods', ['stripe' => true, 'on_site' => true, 'paypal' => true]);
});

it('creates an order and redirects to PayPal on checkout', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 4200,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::PayPal);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('initiate')->once()->andReturnUsing(
        function (User $u, Order $order): PaymentResult {
            $order->update(['provider_session_id' => 'PAYPAL-TESTORDER-1']);

            return PaymentResult::redirect(
                redirect()->away('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-TESTORDER-1')
            );
        }
    );

    app(PaymentProviderManager::class)->register($mockProvider);

    $response = $this->actingAs($user)->post('/cart/checkout', ['payment_method' => 'paypal']);
    $response->assertRedirect();

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::PayPal);
    expect($order->total)->toBe(4200);
    expect($order->currency)->toBe(strtolower((string) config('cashier.currency', 'eur')));
    expect($order->provider_session_id)->toBe('PAYPAL-TESTORDER-1');

    expect($cart->fresh()->items)->toHaveCount(0);
});

it('rejects paypal checkout when the provider is disabled in shop settings', function () {
    ShopSetting::set('enabled_payment_methods', ['stripe' => true, 'on_site' => true, 'paypal' => false]);

    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 1000,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->post('/cart/checkout', ['payment_method' => 'paypal']);

    $response->assertSessionHasErrors('payment_method');
    expect(Order::where('user_id', $user->id)->count())->toBe(0);
});

it('fulfills order on success URL return with valid capture', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 4200,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::PayPal,
        'subtotal' => 4200,
        'total' => 4200,
        'currency' => 'eur',
        'provider_session_id' => 'PAYPAL-TESTORDER-2',
        'metadata' => json_encode([
            ['ticket_type_id' => $ticketType->id, 'quantity' => 1, 'addon_ids' => []],
        ]),
    ]);

    $order->orderLines()->create([
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'description' => $ticketType->name,
        'quantity' => 1,
        'unit_price' => 4200,
        'total_price' => 4200,
    ]);

    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::PayPal);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('handleSuccess')->once()->andReturnUsing(
        function (Order $o, array $params): bool {
            $o->update([
                'provider_session_id' => $params['paypal_order'] ?? $o->provider_session_id,
                'provider_transaction_id' => 'CAPTURE-TEST-123',
            ]);

            return true;
        }
    );

    app(PaymentProviderManager::class)->register($mockProvider);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/success?paypal_order=PAYPAL-TESTORDER-2")
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->provider_transaction_id)->toBe('CAPTURE-TEST-123');
    expect($order->tickets)->toHaveCount(1);
});

it('does not fulfill the order when the PayPal capture is not completed', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::PayPal,
        'provider_session_id' => 'PAYPAL-DENIED-1',
    ]);

    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::PayPal);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('handleSuccess')->once()->andReturn(false);

    app(PaymentProviderManager::class)->register($mockProvider);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/success?paypal_order=PAYPAL-DENIED-1")
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->tickets)->toHaveCount(0);
});
