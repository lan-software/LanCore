<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Domain\Shop\Jobs\GenerateReceiptPdf;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');

    $plain = 'lci_'.Str::random(60);
    $app = IntegrationApp::factory()->create(['is_active' => true]);
    IntegrationToken::factory()->create([
        'integration_app_id' => $app->id,
        'token' => hash('sha256', $plain),
    ]);
    $this->authHeader = ['Authorization' => 'Bearer '.$plain];
    $this->service = new TicketTokenService(new TicketKeyRing);
});

it('settles on-site payment, checks in, and dispatches the receipt', function (): void {
    Queue::fake();

    $operator = User::factory()->create();
    $order = Order::factory()->onSite()->create([
        'total' => 2500,
        'paid_at' => null,
    ]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Active,
        'order_id' => $order->id,
    ]);
    $payload = $ticket->rotateSignedToken($this->service);

    $response = test()->postJson('/api/entrance/confirm-payment', [
        'token' => $payload,
        'payment_method' => 'cash',
        'amount' => '25.00',
        'operator_id' => $operator->id,
    ], $this->authHeader);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('receipt_sent', true);

    expect($order->fresh()->paid_at)->not->toBeNull();
    expect($order->fresh()->confirmed_by)->toBe($operator->id);
    expect($ticket->fresh()->status)->toBe(TicketStatus::CheckedIn);

    Queue::assertPushed(GenerateReceiptPdf::class);
});

it('accepts confirm-payment without validation_id (backwards-compatible)', function (): void {
    $operator = User::factory()->create();
    $order = Order::factory()->onSite()->create(['total' => 1000, 'paid_at' => null]);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active, 'order_id' => $order->id]);
    $payload = $ticket->rotateSignedToken($this->service);

    test()->postJson('/api/entrance/confirm-payment', [
        'token' => $payload,
        'payment_method' => 'cash',
        'amount' => '10.00',
        'operator_id' => $operator->id,
    ], $this->authHeader)
        ->assertOk()
        ->assertJsonPath('decision', 'valid');
});

it('rejects confirm-payment on amount mismatch', function (): void {
    $operator = User::factory()->create();
    $order = Order::factory()->onSite()->create(['total' => 2500, 'paid_at' => null]);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active, 'order_id' => $order->id]);
    $payload = $ticket->rotateSignedToken($this->service);

    test()->postJson('/api/entrance/confirm-payment', [
        'token' => $payload,
        'payment_method' => 'cash',
        'amount' => '99.99',
        'operator_id' => $operator->id,
    ], $this->authHeader)
        ->assertStatus(422)
        ->assertJsonPath('error', 'amount_mismatch');

    expect($order->fresh()->paid_at)->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Active);
});

it('rejects confirm-payment when the order is already paid', function (): void {
    $operator = User::factory()->create();
    $order = Order::factory()->onSite()->create(['total' => 1500, 'paid_at' => now()]);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active, 'order_id' => $order->id]);
    $payload = $ticket->rotateSignedToken($this->service);

    test()->postJson('/api/entrance/confirm-payment', [
        'token' => $payload,
        'payment_method' => 'cash',
        'amount' => '15.00',
        'operator_id' => $operator->id,
    ], $this->authHeader)
        ->assertStatus(422)
        ->assertJsonPath('error', 'invalid');
});
