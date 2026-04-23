<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
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

it('flips a valid active ticket to CheckedIn and accepts missing validation_id', function (): void {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);
    $payload = $ticket->rotateSignedToken($this->service);

    $response = test()->postJson('/api/entrance/checkin', [
        'token' => $payload,
        'operator_id' => 1,
    ], $this->authHeader);

    $response->assertOk()->assertJsonPath('decision', 'valid');
    expect($ticket->fresh()->status)->toBe(TicketStatus::CheckedIn);
    expect($ticket->fresh()->checked_in_at)->not->toBeNull();
});

it('accepts an explicit validation_id in the payload', function (): void {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);
    $payload = $ticket->rotateSignedToken($this->service);

    test()->postJson('/api/entrance/checkin', [
        'token' => $payload,
        'validation_id' => 'aud_deadbeef',
        'operator_id' => 1,
    ], $this->authHeader)->assertOk()->assertJsonPath('decision', 'valid');

    expect($ticket->fresh()->status)->toBe(TicketStatus::CheckedIn);
});

it('returns already_checked_in when called on a CheckedIn ticket', function (): void {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);
    $payload = $ticket->rotateSignedToken($this->service);

    test()->postJson('/api/entrance/checkin', [
        'token' => $payload,
        'operator_id' => 1,
    ], $this->authHeader)->assertOk();

    test()->postJson('/api/entrance/checkin', [
        'token' => $payload,
        'operator_id' => 1,
    ], $this->authHeader)
        ->assertOk()
        ->assertJsonPath('decision', 'already_checked_in');
});

it('returns validation_id alongside audit_id in responses', function (): void {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);
    $payload = $ticket->rotateSignedToken($this->service);

    $response = test()->postJson('/api/entrance/validate', [
        'token' => $payload,
        'operator_id' => 1,
    ], $this->authHeader)->assertOk();

    $response->assertJsonStructure(['audit_id', 'validation_id']);
    expect($response->json('validation_id'))->toBe($response->json('audit_id'));
});
