<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Domain\Ticketing\Security\TicketKeyRing;
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
});

it('requires bearer auth', function (): void {
    $this->getJson('/api/entrance/signing-keys')->assertUnauthorized();
});

it('returns a JWKS document with all verify kids and no private material', function (): void {
    $dir = (string) config('tickets.signing.keys_path');
    file_put_contents($dir.'/kid20260201b.key', sodium_crypto_sign_keypair());
    config()->set('tickets.signing.verify_kids', ['kid20260101a', 'kid20260201b']);
    app()->forgetInstance(TicketKeyRing::class);

    $response = $this->getJson('/api/entrance/signing-keys', $this->authHeader)->assertOk();

    $json = $response->json();
    expect($json)->toHaveKey('keys');
    expect($json['keys'])->toHaveCount(2);

    foreach ($json['keys'] as $key) {
        expect($key)->toHaveKeys(['kid', 'kty', 'crv', 'x']);
        expect($key['kty'])->toBe('OKP');
        expect($key['crv'])->toBe('Ed25519');
        expect($key)->not->toHaveKey('d'); // no private scalar
    }
});
