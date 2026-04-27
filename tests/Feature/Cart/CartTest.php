<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CartItem;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('shows the cart page for authenticated users', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('cart/Index')
                ->has('cartItems')
                ->has('subtotal')
                ->has('total')
        );
});

it('shows empty cart state', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('cart/Index')
                ->has('cartItems', 0)
                ->where('subtotal', 0)
                ->where('total', 0)
        );
});

it('redirects to profile when user has incomplete profile', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => $ticketType->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('profileAlert', __('shop.cart.profile_incomplete'));
});

it('can add a ticket type to the cart', function () {
    $user = User::factory()->withCompleteProfile()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 2500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => $ticketType->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ])
        ->assertRedirect();

    $cart = Cart::where('user_id', $user->id)->first();
    expect($cart)->not->toBeNull();
    expect($cart->event_id)->toBe($event->id);
    expect($cart->items)->toHaveCount(1);
    expect($cart->items->first()->purchasable_type)->toBe(TicketType::class);
    expect($cart->items->first()->quantity)->toBe(1);
});

it('can add an addon to the cart', function () {
    $user = User::factory()->withCompleteProfile()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $addon = Addon::factory()->create([
        'event_id' => $event->id,
        'price' => 500,
    ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'addon',
            'purchasable_id' => $addon->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ])
        ->assertRedirect();

    $cart = Cart::where('user_id', $user->id)->first();
    expect($cart->items)->toHaveCount(1);
    expect($cart->items->first()->purchasable_type)->toBe(Addon::class);
});

it('increments quantity when adding the same item again', function () {
    $user = User::factory()->withCompleteProfile()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => $ticketType->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => $ticketType->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ]);

    $cart = Cart::where('user_id', $user->id)->first();
    expect($cart->items)->toHaveCount(1);
    expect($cart->items->first()->quantity)->toBe(2);
});

it('can update item quantity', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch("/cart/items/{$item->id}", ['quantity' => 3])
        ->assertRedirect();

    expect($item->fresh()->quantity)->toBe(3);
});

it('can remove an item from the cart', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->delete("/cart/items/{$item->id}")
        ->assertRedirect();

    expect(CartItem::find($item->id))->toBeNull();
});

it('clears cart event when last item is removed', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)->delete("/cart/items/{$item->id}");

    expect($cart->fresh()->event_id)->toBeNull();
});

it('can apply a valid voucher code', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);
    $voucher = Voucher::factory()->create(['code' => 'TESTCODE']);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->post('/cart/voucher', ['voucher_code' => 'TESTCODE'])
        ->assertRedirect();

    expect($cart->fresh()->voucher_code)->toBe('TESTCODE');
});

it('rejects an invalid voucher code', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
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

    $this->actingAs($user)
        ->post('/cart/voucher', ['voucher_code' => 'INVALID'])
        ->assertSessionHasErrors('voucher_code');
});

it('rejects expired voucher code', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);
    Voucher::factory()->expired()->create(['code' => 'EXPIRED']);

    $cart = Cart::create(['user_id' => $user->id, 'event_id' => $event->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->post('/cart/voucher', ['voucher_code' => 'EXPIRED'])
        ->assertSessionHasErrors('voucher_code');
});

it('can remove a voucher from the cart', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'voucher_code' => 'TESTCODE',
    ]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->delete('/cart/voucher')
        ->assertRedirect();

    expect($cart->fresh()->voucher_code)->toBeNull();
});

it('prevents adding unavailable items to the cart', function () {
    $user = User::factory()->withCompleteProfile()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->addDay(),
        'purchase_until' => now()->addDays(2),
    ]);

    $this->actingAs($user)
        ->post('/cart/items', [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => $ticketType->id,
            'quantity' => 1,
            'event_id' => $event->id,
        ])
        ->assertSessionHasErrors('cart');
});

it('prevents another user from modifying a cart item', function () {
    $user1 = User::factory()->withRole(RoleName::User)->create();
    $user2 = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $cart = Cart::create(['user_id' => $user1->id, 'event_id' => $event->id]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user2)
        ->patch("/cart/items/{$item->id}", ['quantity' => 5])
        ->assertForbidden();
});

it('shows correct subtotal in cart page', function () {
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
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('cart/Index')
                ->has('cartItems', 1)
                ->where('subtotal', 5000)
                ->where('total', 5000)
        );
});

it('shows discount when voucher is applied', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 10000,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);
    Voucher::factory()->create([
        'code' => 'SAVE10',
        'discount_percent' => 10,
    ]);

    $cart = Cart::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'voucher_code' => 'SAVE10',
    ]);
    CartItem::create([
        'cart_id' => $cart->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('cart/Index')
                ->where('subtotal', 10000)
                ->where('discount', 1000)
                ->where('total', 9000)
        );
});

it('shows available payment methods on the cart page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('cart/Index')
                ->has('paymentMethods')
        );
});

it('can checkout with on-site payment method', function () {
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

    $this->actingAs($user)
        ->post('/cart/checkout', ['payment_method' => 'on_site'])
        ->assertRedirect();

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->payment_method->value)->toBe('on_site');
    expect($order->total)->toBe(2500);
    expect($order->paid_at)->toBeNull();
    expect($order->tickets)->toHaveCount(1);
});

it('clears the cart after successful on-site checkout', function () {
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

    $this->actingAs($user)
        ->post('/cart/checkout', ['payment_method' => 'on_site']);

    $cart->refresh();
    expect($cart->event_id)->toBeNull();
    expect($cart->items)->toHaveCount(0);
});

it('requires a payment method when checking out', function () {
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

    $this->actingAs($user)
        ->post('/cart/checkout', [])
        ->assertSessionHasErrors('payment_method');
});

it('rejects an invalid payment method', function () {
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

    $this->actingAs($user)
        ->post('/cart/checkout', ['payment_method' => 'bitcoin'])
        ->assertSessionHasErrors('payment_method');
});
