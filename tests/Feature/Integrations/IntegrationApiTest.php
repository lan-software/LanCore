<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

function createAppWithToken(array $appAttributes = [], bool $revoked = false, bool $expired = false): array
{
    $plainToken = 'lci_'.Str::random(60);

    $app = IntegrationApp::factory()->create($appAttributes);
    $tokenData = [
        'name' => 'Test Token',
        'token' => hash('sha256', $plainToken),
        'plain_text_prefix' => substr($plainToken, 0, 8),
    ];

    if ($revoked) {
        $tokenData['revoked_at'] = now();
    }

    if ($expired) {
        $tokenData['expires_at'] = now()->subDay();
    }

    $token = $app->tokens()->create($tokenData);

    return ['app' => $app, 'token' => $token, 'plain_text' => $plainToken];
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

it('rejects requests without a bearer token', function () {
    $this->getJson('/api/integration/user/me')
        ->assertUnauthorized()
        ->assertJson(['error' => 'Missing or invalid integration token']);
});

it('rejects requests with an invalid token', function () {
    $this->getJson('/api/integration/user/me', [
        'Authorization' => 'Bearer lci_invalid_token_here',
    ])
        ->assertUnauthorized()
        ->assertJson(['error' => 'Invalid integration token']);
});

it('rejects requests with a non-lci prefix token', function () {
    $this->getJson('/api/integration/user/me', [
        'Authorization' => 'Bearer some_random_token',
    ])
        ->assertUnauthorized()
        ->assertJson(['error' => 'Missing or invalid integration token']);
});

it('rejects revoked tokens', function () {
    $data = createAppWithToken(revoked: true);

    $this->getJson('/api/integration/user/me', [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertForbidden()
        ->assertJson(['error' => 'Token is revoked or expired']);
});

it('rejects expired tokens', function () {
    $data = createAppWithToken(expired: true);

    $this->getJson('/api/integration/user/me', [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertForbidden()
        ->assertJson(['error' => 'Token is revoked or expired']);
});

it('rejects tokens from inactive apps', function () {
    $data = createAppWithToken(['is_active' => false]);

    $this->getJson('/api/integration/user/me', [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertForbidden()
        ->assertJson(['error' => 'Integration app is inactive']);
});

it('updates last_used_at on successful authentication', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);
    $user = User::factory()->create();

    expect($data['token']->last_used_at)->toBeNull();

    $this->actingAs($user)
        ->getJson('/api/integration/user/me', [
            'Authorization' => "Bearer {$data['plain_text']}",
        ])
        ->assertSuccessful();

    $data['token']->refresh();
    expect($data['token']->last_used_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| GET /api/integration/user/me
|--------------------------------------------------------------------------
*/

it('returns user data for authenticated user', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);
    $user = User::factory()->create(['name' => 'Test User', 'username' => 'testuser']);

    $response = $this->actingAs($user)
        ->getJson('/api/integration/user/me', [
            'Authorization' => "Bearer {$data['plain_text']}",
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['id', 'username', 'name', 'locale', 'avatar_url', 'avatar_source', 'profile_url', 'created_at']]);

    expect($response->json('data.id'))->toBe($user->id);
    expect($response->json('data.username'))->toBe('testuser');
    expect($response->json('data.name'))->toBe('Test User');
    expect($response->json('data'))->not->toHaveKey('email');
    expect($response->json('data'))->not->toHaveKey('roles');
});

it('returns 401 when no user is authenticated for /me', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);

    $this->getJson('/api/integration/user/me', [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertUnauthorized()
        ->assertJson(['error' => 'No authenticated user']);
});

it('includes email when user:email scope is granted', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read', 'user:email']]);
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->actingAs($user)
        ->getJson('/api/integration/user/me', [
            'Authorization' => "Bearer {$data['plain_text']}",
        ])
        ->assertSuccessful();

    expect($response->json('data.email'))->toBe('test@example.com');
});

it('includes roles when user:roles scope is granted', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read', 'user:roles']]);
    $user = User::factory()->withRole(RoleName::Admin)->create();

    $response = $this->actingAs($user)
        ->getJson('/api/integration/user/me', [
            'Authorization' => "Bearer {$data['plain_text']}",
        ])
        ->assertSuccessful();

    expect($response->json('data.roles'))->toContain('admin');
});

it('returns 403 when user:read scope is missing', function () {
    $data = createAppWithToken(['allowed_scopes' => []]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/integration/user/me', [
            'Authorization' => "Bearer {$data['plain_text']}",
        ])
        ->assertForbidden()
        ->assertJson(['error' => 'Insufficient scopes']);
});

/*
|--------------------------------------------------------------------------
| POST /api/integration/user/resolve
|--------------------------------------------------------------------------
*/

it('resolves a user by id', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);
    $user = User::factory()->create(['name' => 'Jane Doe', 'username' => 'janedoe']);

    $response = $this->postJson('/api/integration/user/resolve', [
        'user_id' => $user->id,
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertSuccessful();

    expect($response->json('data.id'))->toBe($user->id);
    expect($response->json('data.username'))->toBe('janedoe');
    expect($response->json('data.name'))->toBe('Jane Doe');
});

it('resolves a user by email', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read', 'user:email']]);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/integration/user/resolve', [
        'email' => 'jane@example.com',
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertSuccessful();

    expect($response->json('data.id'))->toBe($user->id);
    expect($response->json('data.email'))->toBe('jane@example.com');
});

it('returns 404 when user is not found', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);

    $this->postJson('/api/integration/user/resolve', [
        'user_id' => 99999,
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertNotFound()
        ->assertJson(['error' => 'User not found']);
});

it('validates that user_id or email is required for resolve', function () {
    $data = createAppWithToken(['allowed_scopes' => ['user:read']]);

    $this->postJson('/api/integration/user/resolve', [], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])
        ->assertUnprocessable();
});
