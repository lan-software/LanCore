<?php

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Laravel\Cashier\Events\WebhookReceived;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

function buildWebhookPayload(string $type, array $metadata = [], array $extra = []): array
{
    return [
        'type' => $type,
        'data' => [
            'object' => array_merge([
                'id' => 'cs_test_'.fake()->uuid(),
                'payment_intent' => 'pi_test_'.fake()->uuid(),
                'metadata' => $metadata,
            ], $extra),
        ],
    ];
}

it('fulfills a pending order on checkout.session.completed webhook', function () {
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

    $payload = buildWebhookPayload('checkout.session.completed', ['order_id' => $order->id]);

    WebhookReceived::dispatch($payload);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->provider_session_id)->not->toBeNull();
    expect($order->provider_transaction_id)->not->toBeNull();
    expect($order->tickets)->toHaveCount(1);
});

it('does not duplicate tickets for already-completed orders', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::Stripe,
        'status' => OrderStatus::Completed,
    ]);

    $payload = buildWebhookPayload('checkout.session.completed', ['order_id' => $order->id]);

    WebhookReceived::dispatch($payload);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->tickets)->toHaveCount(0);
});

it('ignores webhooks without order_id in metadata', function () {
    $payload = buildWebhookPayload('checkout.session.completed', []);

    WebhookReceived::dispatch($payload);

    expect(Order::where('status', OrderStatus::Completed)->count())->toBe(0);
});

it('ignores non-checkout webhook event types', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::Stripe,
    ]);

    $payload = buildWebhookPayload('customer.subscription.created', ['order_id' => $order->id]);

    WebhookReceived::dispatch($payload);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
});
