<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;

it('creates an integration app via artisan command', function () {
    $this->artisan('integration:create', [
        'name' => 'LanShout',
        '--scopes' => ['user:read', 'user:email'],
    ])
        ->assertSuccessful();

    expect(IntegrationApp::where('slug', 'lanshout')->exists())->toBeTrue();

    $app = IntegrationApp::where('slug', 'lanshout')->first();
    expect($app->name)->toBe('LanShout');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:email']);
});

it('creates an integration app with custom slug', function () {
    $this->artisan('integration:create', [
        'name' => 'LanShout Chat',
        '--slug' => 'lanshout-chat',
    ])
        ->assertSuccessful();

    expect(IntegrationApp::where('slug', 'lanshout-chat')->exists())->toBeTrue();
});

it('generates a token via artisan command', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanshout']);

    $this->artisan('integration:token', [
        'app' => 'lanshout',
        'name' => 'Production',
    ])
        ->assertSuccessful();

    expect($app->tokens()->count())->toBe(1);
    expect($app->tokens()->first()->name)->toBe('Production');
});

it('generates a token using app ID', function () {
    $app = IntegrationApp::factory()->create();

    $this->artisan('integration:token', [
        'app' => (string) $app->id,
        'name' => 'By ID',
    ])
        ->assertSuccessful();

    expect($app->tokens()->count())->toBe(1);
});

it('fails to generate a token for nonexistent app', function () {
    $this->artisan('integration:token', [
        'app' => 'nonexistent',
        'name' => 'Token',
    ])
        ->assertFailed();
});

it('lists integration apps', function () {
    IntegrationApp::factory()->count(3)->create();

    $this->artisan('integration:list')
        ->assertSuccessful();
});

it('shows empty message when no apps exist', function () {
    $this->artisan('integration:list')
        ->assertSuccessful();
});

it('lists tokens for an integration app by slug', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanshout']);
    IntegrationToken::factory()->for($app)->count(2)->create();

    $this->artisan('integration:tokens', ['app' => 'lanshout'])
        ->assertSuccessful();
});

it('lists tokens for an integration app by ID', function () {
    $app = IntegrationApp::factory()->create();
    IntegrationToken::factory()->for($app)->create();

    $this->artisan('integration:tokens', ['app' => (string) $app->id])
        ->assertSuccessful();
});

it('shows empty message when no tokens exist', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'empty-app']);

    $this->artisan('integration:tokens', ['app' => 'empty-app'])
        ->assertSuccessful();
});

it('fails to list tokens for nonexistent app', function () {
    $this->artisan('integration:tokens', ['app' => 'nonexistent'])
        ->assertFailed();
});

it('revokes a token via artisan command', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanshout']);
    $token = IntegrationToken::factory()->for($app)->create();

    $this->artisan('integration:revoke-token', [
        'app' => 'lanshout',
        'token' => (string) $token->id,
    ])
        ->assertSuccessful();

    $token->refresh();
    expect($token->revoked_at)->not->toBeNull();
});

it('revokes a token using app ID', function () {
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app)->create();

    $this->artisan('integration:revoke-token', [
        'app' => (string) $app->id,
        'token' => (string) $token->id,
    ])
        ->assertSuccessful();

    $token->refresh();
    expect($token->revoked_at)->not->toBeNull();
});

it('succeeds when revoking an already revoked token', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanshout']);
    $token = IntegrationToken::factory()->revoked()->for($app)->create();

    $this->artisan('integration:revoke-token', [
        'app' => 'lanshout',
        'token' => (string) $token->id,
    ])
        ->assertSuccessful();
});

it('fails to revoke a token for nonexistent app', function () {
    $this->artisan('integration:revoke-token', [
        'app' => 'nonexistent',
        'token' => '1',
    ])
        ->assertFailed();
});

it('fails to revoke a nonexistent token', function () {
    $app = IntegrationApp::factory()->create(['slug' => 'lanshout']);

    $this->artisan('integration:revoke-token', [
        'app' => 'lanshout',
        'token' => '99999',
    ])
        ->assertFailed();
});
