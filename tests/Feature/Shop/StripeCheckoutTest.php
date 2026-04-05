<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Contracts\PaymentProvider;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CartItem;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('creates an order and redirects to Stripe on checkout', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
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

    // Mock the Stripe payment provider to avoid real Stripe API calls
    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::Stripe);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('initiate')->once()->andReturnUsing(
        function (User $u, Order $order) {
            return PaymentResult::redirect(
                redirect()->away('https://checkout.stripe.com/test_session')
            );
        }
    );

    $manager = app(PaymentProviderManager::class);
    $manager->register($mockProvider);

    $response = $this->actingAs($user)
        ->post('/cart/checkout', ['payment_method' => 'stripe']);

    $response->assertRedirect();

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::Stripe);
    expect($order->total)->toBe(2500);

    // Cart should be cleared after checkout
    expect($cart->fresh()->items)->toHaveCount(0);
});

it('fulfills order on success URL return with valid session', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::Stripe,
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

    // Mock the payment provider to simulate successful Stripe payment
    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::Stripe);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('handleSuccess')->once()->andReturnUsing(
        function (Order $o, array $params) {
            $o->update([
                'provider_session_id' => $params['session_id'],
                'provider_transaction_id' => 'pi_test_456',
            ]);

            return true;
        }
    );

    $manager = app(PaymentProviderManager::class);
    $manager->register($mockProvider);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/success?session_id=cs_test_123")
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->provider_session_id)->toBe('cs_test_123');
    expect($order->tickets)->toHaveCount(1);
});

it('does not fulfill order when payment is not completed', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::Stripe,
    ]);

    // Mock the payment provider to simulate unpaid session
    $mockProvider = Mockery::mock(PaymentProvider::class);
    $mockProvider->shouldReceive('method')->andReturn(PaymentMethod::Stripe);
    $mockProvider->shouldReceive('requiresRedirect')->andReturn(true);
    $mockProvider->shouldReceive('handleSuccess')->once()->andReturn(false);

    $manager = app(PaymentProviderManager::class);
    $manager->register($mockProvider);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/success?session_id=cs_test_unpaid")
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->tickets)->toHaveCount(0);
});

it('marks order as failed on cancel', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::Stripe,
    ]);

    $this->actingAs($user)
        ->get("/cart/checkout/{$order->id}/cancel")
        ->assertRedirect('/cart');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Failed);
});
