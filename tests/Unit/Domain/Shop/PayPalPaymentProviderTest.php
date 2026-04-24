<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Domain\Shop\PaymentProviders\PayPalPaymentProvider;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Srmklive\PayPal\Testing\MockPayPalClient;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

function makePayPalProvider(MockPayPalClient $http): PayPalPaymentProvider
{
    return new PayPalPaymentProvider(fn () => $http->mockProvider(['currency' => 'EUR']));
}

function makePayPalOrderFixture(): Order
{
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 3500,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::PayPal,
        'subtotal' => 3500,
        'discount' => 0,
        'total' => 3500,
        'currency' => 'eur',
        'invoice_number' => 'TEST-0001',
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'description' => $ticketType->name,
        'quantity' => 1,
        'unit_price' => 3500,
        'total_price' => 3500,
    ]);

    return $order->fresh(['orderLines']);
}

it('creates a PayPal order and redirects to the payer-action link', function () {
    $http = new MockPayPalClient;
    $http->addResponse([
        'id' => 'PAYPAL-ORDER-ABC',
        'status' => 'CREATED',
        'links' => [
            ['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/orders/PAYPAL-ORDER-ABC'],
            ['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-ABC'],
        ],
    ]);

    $order = makePayPalOrderFixture();
    $provider = makePayPalProvider($http);

    $result = $provider->initiate($order->user, $order);

    expect($order->fresh()->provider_session_id)->toBe('PAYPAL-ORDER-ABC');
    expect($result->redirect)->not->toBeNull();
    expect($result->redirect->getTargetUrl())
        ->toBe('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-ABC');
});

it('captures a PayPal order and records the capture id on handleSuccess', function () {
    $http = new MockPayPalClient;
    $http->addResponse([
        'id' => 'PAYPAL-ORDER-XYZ',
        'status' => 'COMPLETED',
        'purchase_units' => [[
            'payments' => [
                'captures' => [[
                    'id' => 'CAPTURE-XYZ-001',
                    'status' => 'COMPLETED',
                ]],
            ],
        ]],
    ]);

    $order = makePayPalOrderFixture();
    $order->update(['provider_session_id' => 'PAYPAL-ORDER-XYZ']);

    $result = makePayPalProvider($http)->handleSuccess($order, ['paypal_order' => 'PAYPAL-ORDER-XYZ']);

    expect($result)->toBeTrue();
    expect($order->fresh()->provider_transaction_id)->toBe('CAPTURE-XYZ-001');
});

it('returns false when the PayPal capture is not completed', function () {
    $http = new MockPayPalClient;
    $http->addResponse([
        'id' => 'PAYPAL-ORDER-PEND',
        'status' => 'PAYER_ACTION_REQUIRED',
    ]);

    $order = makePayPalOrderFixture();
    $order->update(['provider_session_id' => 'PAYPAL-ORDER-PEND']);

    expect(makePayPalProvider($http)->handleSuccess($order, ['paypal_order' => 'PAYPAL-ORDER-PEND']))
        ->toBeFalse();
    expect($order->fresh()->provider_transaction_id)->toBeNull();
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('advertises PayPal as a redirect-based payment method', function () {
    $provider = makePayPalProvider(new MockPayPalClient);

    expect($provider->method())->toBe(PaymentMethod::PayPal);
    expect($provider->requiresRedirect())->toBeTrue();
});
