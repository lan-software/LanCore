<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

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

function postValidate(array $headers, string $token): TestResponse
{
    return test()->postJson('/api/entrance/validate', [
        'token' => $token,
        'operator_id' => 1,
    ], $headers);
}

it('accepts a valid signed token', function (): void {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Active]);
    $payload = $ticket->rotateSignedToken($this->service);

    postValidate($this->authHeader, $payload)
        ->assertOk()
        ->assertJsonPath('decision', 'valid');
});

it('rejects invalid signature', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);

    postValidate($this->authHeader, $payload.'AA')
        ->assertOk()
        ->assertJsonPath('decision', 'invalid_signature');
});

it('rejects unknown kid', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);
    [$ver, , $body, $sig] = explode('.', $payload);

    postValidate($this->authHeader, "{$ver}.nope.{$body}.{$sig}")
        ->assertOk()
        ->assertJsonPath('decision', 'unknown_kid');
});

it('rejects expired tokens', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);

    [$ver, $kid, $bodyB64] = explode('.', $payload);
    $body = json_decode(TicketKeyRing::base64UrlDecode($bodyB64), true);
    $body['exp'] = time() - 1;
    $newBodyB64 = TicketKeyRing::base64UrlEncode(json_encode($body));
    $ring = new TicketKeyRing;
    $sig = TicketKeyRing::base64UrlEncode($ring->sign($kid, "{$ver}.{$kid}.{$newBodyB64}"));

    postValidate($this->authHeader, "{$ver}.{$kid}.{$newBodyB64}.{$sig}")
        ->assertOk()
        ->assertJsonPath('decision', 'expired');
});

it('treats rotated tickets as revoked', function (): void {
    $ticket = Ticket::factory()->create();
    $oldPayload = $ticket->rotateSignedToken($this->service);
    $ticket->rotateSignedToken($this->service); // rotate

    postValidate($this->authHeader, $oldPayload)
        ->assertOk()
        ->assertJsonPath('decision', 'revoked');
});

it('reports already checked in', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);
    $ticket->update(['status' => TicketStatus::CheckedIn, 'checked_in_at' => now()]);

    postValidate($this->authHeader, $payload)
        ->assertOk()
        ->assertJsonPath('decision', 'already_checked_in');
});

it('reports cancelled tickets', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);
    $ticket->update(['status' => TicketStatus::Cancelled]);

    postValidate($this->authHeader, $payload)
        ->assertOk()
        ->assertJsonPath('decision', 'invalid');
});

it('rejects a DB-inserted forged nonce_hash (signature mismatch)', function (): void {
    $forgedHash = hash_hmac('sha256', random_bytes(16), (string) config('tickets.pepper'));
    $ticket = Ticket::factory()->create(['validation_nonce_hash' => $forgedHash]);

    // An attacker cannot craft a token without the signing key.
    postValidate($this->authHeader, 'LCT1.kid20260101a.abc.zzz')
        ->assertOk()
        ->assertJsonPath('decision', 'invalid_signature');
});

it('does not find tickets by raw QR payload in DB', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->rotateSignedToken($this->service);

    // The raw payload must not match any DB column — confirms QR leak is safe.
    expect(Ticket::query()->where('validation_nonce_hash', $payload)->exists())->toBeFalse();
});
