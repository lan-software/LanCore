<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-011
 * @see docs/mil-std-498/SRS.md SHP-F-020
 */

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Http\Controllers\PayPalWebhookController;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * PayPal client stub that short-circuits `verifyWebHookLocally` so tests
 * don't need to fetch PayPal's live signing cert. Toggle $valid to simulate
 * a tampered / malformed webhook payload.
 */
class StubPayPalClient extends PayPalClient
{
    public function __construct(public bool $valid = true)
    {
        parent::__construct();
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function verifyWebHookLocally(array $headers, string $webhook_id, string $raw_body): bool
    {
        return $this->valid;
    }
}

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    config()->set('paypal.webhook_id', 'WH-TEST-0001');
});

function bindPayPalWebhookController(bool $signatureValid = true): void
{
    app()->bind(PayPalWebhookController::class, fn ($app) => new PayPalWebhookController(
        fn () => new StubPayPalClient($signatureValid),
        $app->make(FulfillOrder::class),
    ));
}

function makePayPalPendingOrder(): Order
{
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create(['status' => 'published']);
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'price' => 3000,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $order = Order::factory()->pending()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'payment_method' => PaymentMethod::PayPal,
        'subtotal' => 3000,
        'total' => 3000,
        'currency' => 'eur',
        'provider_session_id' => 'PAYPAL-WH-ORDER-1',
        'metadata' => json_encode([
            ['ticket_type_id' => $ticketType->id, 'quantity' => 1, 'addon_ids' => []],
        ]),
    ]);

    $order->orderLines()->create([
        'purchasable_type' => TicketType::class,
        'purchasable_id' => $ticketType->id,
        'description' => $ticketType->name,
        'quantity' => 1,
        'unit_price' => 3000,
        'total_price' => 3000,
    ]);

    return $order->fresh(['orderLines']);
}

it('rejects webhooks with an invalid signature', function () {
    bindPayPalWebhookController(signatureValid: false);

    $payload = [
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => ['id' => 'CAP-1', 'custom_id' => '1'],
    ];

    $this->postJson('/webhooks/paypal', $payload)
        ->assertStatus(401)
        ->assertJsonPath('status', 'verification_failed');
});

it('returns 503 when PAYPAL_WEBHOOK_ID is unset', function () {
    config()->set('paypal.webhook_id', '');
    bindPayPalWebhookController();

    $this->postJson('/webhooks/paypal', ['event_type' => 'PAYMENT.CAPTURE.COMPLETED'])
        ->assertStatus(503)
        ->assertJsonPath('status', 'unconfigured');
});

it('fulfills a pending PayPal order on PAYMENT.CAPTURE.COMPLETED', function () {
    bindPayPalWebhookController();
    $order = makePayPalPendingOrder();

    $this->postJson('/webhooks/paypal', [
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAPTURE-XYZ-456',
            'custom_id' => (string) $order->id,
        ],
    ])->assertOk()->assertJsonPath('status', 'fulfilled');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->provider_transaction_id)->toBe('CAPTURE-XYZ-456');
    expect($order->tickets)->toHaveCount(1);
});

it('is idempotent when a webhook fires after the order is already completed', function () {
    bindPayPalWebhookController();
    $order = makePayPalPendingOrder();
    $order->update(['status' => OrderStatus::Completed, 'provider_transaction_id' => 'CAPTURE-FIRST']);

    $this->postJson('/webhooks/paypal', [
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAPTURE-SECOND',
            'custom_id' => (string) $order->id,
        ],
    ])->assertOk()->assertJsonPath('status', 'already_fulfilled');

    expect($order->fresh()->provider_transaction_id)->toBe('CAPTURE-FIRST');
});

it('marks the order as failed on PAYMENT.CAPTURE.DENIED', function () {
    bindPayPalWebhookController();
    $order = makePayPalPendingOrder();

    $this->postJson('/webhooks/paypal', [
        'event_type' => 'PAYMENT.CAPTURE.DENIED',
        'resource' => ['id' => 'CAP-D-1', 'custom_id' => (string) $order->id],
    ])->assertOk()->assertJsonPath('status', 'marked_failed');

    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});

it('ignores unrelated event types', function () {
    bindPayPalWebhookController();

    $this->postJson('/webhooks/paypal', [
        'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
        'resource' => ['id' => 'sub-1'],
    ])->assertOk()->assertJsonPath('status', 'ignored');
});
