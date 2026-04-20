<?php

use App\Domain\Integration\Actions\SetIntegrationTokenPlaintext;
use App\Domain\Integration\Models\IntegrationApp;

it('hashes the plaintext and stores the 8-char prefix', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanbrackets']);
    $plaintext = 'lci_plainfixed-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    $token = app(SetIntegrationTokenPlaintext::class)
        ->execute($app, 'config-seeded', $plaintext);

    expect($token->token)->toBe(hash('sha256', $plaintext));
    expect($token->plain_text_prefix)->toBe('lci_plai');
    expect($token->name)->toBe('config-seeded');
    expect($token->expires_at)->toBeNull();
});

it('does not leak plaintext in the returned model', function () {
    $app = IntegrationApp::factory()->create();
    $plaintext = 'lci_leaktest-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    $token = app(SetIntegrationTokenPlaintext::class)
        ->execute($app, 'config-seeded', $plaintext);

    expect($token->toArray())->not->toContain($plaintext);
});
