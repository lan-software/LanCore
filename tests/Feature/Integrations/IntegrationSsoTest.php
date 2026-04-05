<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

function createSsoAppWithToken(array $appAttributes = []): array
{
    $plainToken = 'lci_'.Str::random(60);

    $app = IntegrationApp::factory()->create($appAttributes);
    $token = $app->tokens()->create([
        'name' => 'Test Token',
        'token' => hash('sha256', $plainToken),
        'plain_text_prefix' => substr($plainToken, 0, 8),
    ]);

    return ['app' => $app, 'token' => $token, 'plain_text' => $plainToken];
}

/*
|--------------------------------------------------------------------------
| GET /sso/authorize
|--------------------------------------------------------------------------
*/

it('redirects an authenticated user back with an authorization code', function () {
    $app = IntegrationApp::factory()->create([
        'slug' => 'lanshout',
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'allowed_scopes' => ['user:read'],
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/sso/authorize?app=lanshout&redirect_uri='.urlencode('https://shout.lan.party/auth/lancore/callback'));

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('https://shout.lan.party/auth/lancore/callback?code=');

    // Extract code from redirect URL
    parse_str(parse_url($location, PHP_URL_QUERY), $params);
    $code = $params['code'];

    expect($code)->toHaveLength(64);

    // Verify code is stored in cache
    $cached = Cache::get("sso_code:{$code}");
    expect($cached)->not->toBeNull();
    expect($cached['user_id'])->toBe($user->id);
    expect($cached['integration_app_id'])->toBe($app->id);
});

it('redirects unauthenticated users to login', function () {
    IntegrationApp::factory()->create([
        'slug' => 'lanshout',
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'is_active' => true,
    ]);

    $response = $this->get('/sso/authorize?app=lanshout&redirect_uri='.urlencode('https://shout.lan.party/auth/lancore/callback'));

    $response->assertRedirect('/login');
});

it('returns 404 for unknown app slug', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/sso/authorize?app=nonexistent&redirect_uri='.urlencode('https://example.com/callback'))
        ->assertNotFound();
});

it('returns 404 for inactive app', function () {
    IntegrationApp::factory()->create([
        'slug' => 'inactive-app',
        'callback_url' => 'https://example.com/callback',
        'is_active' => false,
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/sso/authorize?app=inactive-app&redirect_uri='.urlencode('https://example.com/callback'))
        ->assertNotFound();
});

it('returns 403 when redirect_uri does not match callback_url', function () {
    IntegrationApp::factory()->create([
        'slug' => 'lanshout',
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/sso/authorize?app=lanshout&redirect_uri='.urlencode('https://evil.example.com/steal'))
        ->assertForbidden();
});

it('validates required parameters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/sso/authorize')
        ->assertInvalid(['app', 'redirect_uri']);
});

it('appends code with & when redirect_uri already has query params', function () {
    $app = IntegrationApp::factory()->create([
        'slug' => 'lanshout',
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $redirectUri = 'https://shout.lan.party/auth/lancore/callback?state=abc123';

    $response = $this->actingAs($user)
        ->get('/sso/authorize?app=lanshout&redirect_uri='.urlencode($redirectUri));

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('https://shout.lan.party/auth/lancore/callback?state=abc123&code=');
});

/*
|--------------------------------------------------------------------------
| POST /api/integration/sso/exchange
|--------------------------------------------------------------------------
*/

it('exchanges a valid authorization code for user data', function () {
    $data = createSsoAppWithToken([
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'allowed_scopes' => ['user:read'],
    ]);
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $code = Str::random(64);
    Cache::put("sso_code:{$code}", [
        'user_id' => $user->id,
        'integration_app_id' => $data['app']->id,
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/integration/sso/exchange', [
        'code' => $code,
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['data' => ['id', 'username', 'locale', 'avatar_url', 'created_at']]);

    expect($response->json('data.id'))->toBe($user->id);
    expect($response->json('data.username'))->toBe('Jane Doe');
});

it('returns user email when user:email scope is granted', function () {
    $data = createSsoAppWithToken([
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'allowed_scopes' => ['user:read', 'user:email'],
    ]);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $code = Str::random(64);
    Cache::put("sso_code:{$code}", [
        'user_id' => $user->id,
        'integration_app_id' => $data['app']->id,
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/integration/sso/exchange', [
        'code' => $code,
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ]);

    $response->assertSuccessful();
    expect($response->json('data.email'))->toBe('jane@example.com');
});

it('returns user roles when user:roles scope is granted', function () {
    $data = createSsoAppWithToken([
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'allowed_scopes' => ['user:read', 'user:roles'],
    ]);
    $user = User::factory()->withRole(RoleName::Admin)->create();

    $code = Str::random(64);
    Cache::put("sso_code:{$code}", [
        'user_id' => $user->id,
        'integration_app_id' => $data['app']->id,
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/integration/sso/exchange', [
        'code' => $code,
    ], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ]);

    $response->assertSuccessful();
    expect($response->json('data.roles'))->toContain('admin');
});

it('invalidates the code after single use', function () {
    $data = createSsoAppWithToken([
        'callback_url' => 'https://shout.lan.party/auth/lancore/callback',
        'allowed_scopes' => ['user:read'],
    ]);
    $user = User::factory()->create();

    $code = Str::random(64);
    Cache::put("sso_code:{$code}", [
        'user_id' => $user->id,
        'integration_app_id' => $data['app']->id,
    ], now()->addMinutes(5));

    // First exchange succeeds
    $this->postJson('/api/integration/sso/exchange', ['code' => $code], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertSuccessful();

    // Second exchange fails (code consumed)
    $this->postJson('/api/integration/sso/exchange', ['code' => $code], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertBadRequest()
        ->assertJson(['error' => 'Invalid or expired authorization code']);
});

it('rejects an expired authorization code', function () {
    $data = createSsoAppWithToken([
        'allowed_scopes' => ['user:read'],
    ]);
    $user = User::factory()->create();

    $code = Str::random(64);
    // Don't put in cache (simulate expired/never existed)

    $this->postJson('/api/integration/sso/exchange', ['code' => $code], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertBadRequest()
        ->assertJson(['error' => 'Invalid or expired authorization code']);
});

it('rejects a code that belongs to a different app', function () {
    $data = createSsoAppWithToken([
        'allowed_scopes' => ['user:read'],
    ]);
    $otherApp = IntegrationApp::factory()->create();
    $user = User::factory()->create();

    $code = Str::random(64);
    Cache::put("sso_code:{$code}", [
        'user_id' => $user->id,
        'integration_app_id' => $otherApp->id, // Different app
    ], now()->addMinutes(5));

    $this->postJson('/api/integration/sso/exchange', ['code' => $code], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertForbidden()
        ->assertJson(['error' => 'Authorization code does not belong to this application']);
});

it('validates the code parameter', function () {
    $data = createSsoAppWithToken();

    $this->postJson('/api/integration/sso/exchange', [], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertInvalid(['code']);
});

it('rejects codes with wrong length', function () {
    $data = createSsoAppWithToken();

    $this->postJson('/api/integration/sso/exchange', ['code' => 'short'], [
        'Authorization' => "Bearer {$data['plain_text']}",
    ])->assertInvalid(['code']);
});
