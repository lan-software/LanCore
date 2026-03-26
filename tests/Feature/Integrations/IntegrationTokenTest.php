<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to create a token for an integration app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($admin)
        ->post("/integrations/{$app->id}/tokens", [
            'name' => 'Production Token',
        ])
        ->assertRedirect()
        ->assertSessionHas('newToken');

    expect($app->tokens()->count())->toBe(1);

    $token = $app->tokens()->first();
    expect($token->name)->toBe('Production Token');
    expect($token->plain_text_prefix)->toStartWith('lci_');
    expect($token->revoked_at)->toBeNull();
    expect($token->expires_at)->toBeNull();
});

it('allows admins to create a token with expiration', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();

    $expiresAt = now()->addDays(30)->format('Y-m-d');

    $this->actingAs($admin)
        ->post("/integrations/{$app->id}/tokens", [
            'name' => 'Temporary Token',
            'expires_at' => $expiresAt,
        ])
        ->assertRedirect();

    $token = $app->tokens()->first();
    expect($token->expires_at)->not->toBeNull();
});

it('validates token name is required', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($admin)
        ->post("/integrations/{$app->id}/tokens", [])
        ->assertSessionHasErrors(['name']);
});

it('allows admins to revoke a token', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app)->create();

    $this->actingAs($admin)
        ->delete("/integrations/{$app->id}/tokens/{$token->id}")
        ->assertRedirect();

    $token->refresh();
    expect($token->revoked_at)->not->toBeNull();
});

it('returns 404 when revoking a token from a different app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app1 = IntegrationApp::factory()->create();
    $app2 = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app2)->create();

    $this->actingAs($admin)
        ->delete("/integrations/{$app1->id}/tokens/{$token->id}")
        ->assertNotFound();
});

it('forbids regular users from creating tokens', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($user)
        ->post("/integrations/{$app->id}/tokens", [
            'name' => 'Sneaky Token',
        ])
        ->assertForbidden();

    expect($app->tokens()->count())->toBe(0);
});

it('forbids regular users from revoking tokens', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app)->create();

    $this->actingAs($user)
        ->delete("/integrations/{$app->id}/tokens/{$token->id}")
        ->assertForbidden();

    $token->refresh();
    expect($token->revoked_at)->toBeNull();
});

it('allows admins to rotate a token', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app)->create(['name' => 'Rotate Me']);

    $this->actingAs($admin)
        ->post("/integrations/{$app->id}/tokens/{$token->id}/rotate")
        ->assertRedirect()
        ->assertSessionHas('newToken');

    $token->refresh();
    expect($token->revoked_at)->not->toBeNull();
    expect($app->tokens()->count())->toBe(2);

    $newToken = $app->tokens()->whereNull('revoked_at')->first();
    expect($newToken->name)->toBe('Rotate Me');
});

it('cannot rotate a revoked token', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->revoked()->for($app)->create();

    $this->actingAs($admin)
        ->post("/integrations/{$app->id}/tokens/{$token->id}/rotate")
        ->assertStatus(422);
});

it('cannot rotate a token from a different app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app1 = IntegrationApp::factory()->create();
    $app2 = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app2)->create();

    $this->actingAs($admin)
        ->post("/integrations/{$app1->id}/tokens/{$token->id}/rotate")
        ->assertNotFound();
});

it('forbids regular users from rotating tokens', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $app = IntegrationApp::factory()->create();
    $token = IntegrationToken::factory()->for($app)->create();

    $this->actingAs($user)
        ->post("/integrations/{$app->id}/tokens/{$token->id}/rotate")
        ->assertForbidden();

    expect($app->tokens()->count())->toBe(1);
    $token->refresh();
    expect($token->revoked_at)->toBeNull();
});
