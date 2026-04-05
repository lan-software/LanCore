<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;

it('creates an integration app and token for a known satellite app', function () {
    $this->artisan('integration:setup-dev', ['app' => 'lanbrackets'])
        ->expectsOutputToContain("Integration app 'LanBrackets' created.")
        ->expectsOutputToContain('LANCORE_ENABLED=true')
        ->expectsOutputToContain('LANCORE_TOKEN=lci_')
        ->expectsOutputToContain('LANCORE_APP_SLUG=lanbrackets')
        ->expectsOutputToContain('LANCORE_CALLBACK_URL=http://localhost:81/auth/callback')
        ->assertSuccessful();

    $app = IntegrationApp::where('slug', 'lanbrackets')->first();

    expect($app)->not->toBeNull();
    expect($app->name)->toBe('LanBrackets');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:email', 'user:roles']);
    expect($app->is_active)->toBeTrue();
    expect($app->send_role_updates)->toBeTrue();
    expect($app->callback_url)->toBe('http://localhost:81/auth/callback');
    expect($app->tokens)->toHaveCount(1);
});

it('rejects unknown app slugs', function () {
    $this->artisan('integration:setup-dev', ['app' => 'unknown'])
        ->expectsOutputToContain("Unknown app 'unknown'")
        ->assertFailed();
});

it('generates a new token for an existing app', function () {
    IntegrationApp::factory()->create(['slug' => 'lanshout', 'name' => 'LanShout']);

    $this->artisan('integration:setup-dev', ['app' => 'lanshout'])
        ->expectsConfirmation('Generate a new token for the existing app?', 'yes')
        ->expectsOutputToContain('LANCORE_TOKEN=lci_')
        ->assertSuccessful();

    expect(IntegrationToken::count())->toBe(1);
});
