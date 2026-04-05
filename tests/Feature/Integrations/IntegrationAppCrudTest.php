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

/*
|--------------------------------------------------------------------------
| Index
|--------------------------------------------------------------------------
*/

it('displays the integrations index page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    IntegrationApp::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/integrations')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Index')
            ->has('integrationApps.data', 3)
        );
});

it('searches integrations by name', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    IntegrationApp::factory()->create(['name' => 'LanShout']);
    IntegrationApp::factory()->create(['name' => 'Other App']);

    $this->actingAs($admin)
        ->get('/integrations?search=LanShout')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Index')
            ->has('integrationApps.data', 1)
        );
});

/*
|--------------------------------------------------------------------------
| Create & Store
|--------------------------------------------------------------------------
*/

it('allows admins to store a new integration app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout',
            'slug' => 'lanshout',
            'description' => 'Chat application for LAN events',
            'allowed_scopes' => ['user:read', 'user:email'],
            'is_active' => true,
        ])
        ->assertRedirect('/integrations');

    expect(IntegrationApp::where('slug', 'lanshout')->exists())->toBeTrue();

    $app = IntegrationApp::where('slug', 'lanshout')->first();
    expect($app->name)->toBe('LanShout');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:email']);
    expect($app->is_active)->toBeTrue();
});

it('validates required fields when storing', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [])
        ->assertSessionHasErrors(['name', 'slug']);
});

it('validates slug uniqueness', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    IntegrationApp::factory()->create(['slug' => 'lanshout']);

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout 2',
            'slug' => 'lanshout',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('validates allowed scopes are valid', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'Test App',
            'slug' => 'test-app',
            'allowed_scopes' => ['invalid:scope'],
        ])
        ->assertSessionHasErrors(['allowed_scopes.0']);
});

/*
|--------------------------------------------------------------------------
| Edit & Update
|--------------------------------------------------------------------------
*/

it('allows admins to view the edit page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($admin)
        ->get("/integrations/{$app->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Edit')
            ->has('integrationApp')
            ->has('availableScopes')
        );
});

it('allows admins to update an integration app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch("/integrations/{$app->id}", [
            'name' => 'New Name',
            'description' => 'Updated description',
            'allowed_scopes' => ['user:read', 'user:roles'],
        ])
        ->assertRedirect();

    $app->refresh();
    expect($app->name)->toBe('New Name');
    expect($app->description)->toBe('Updated description');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:roles']);
});

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

it('allows admins to delete an integration app', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();
    IntegrationToken::factory()->for($app)->create();

    $this->actingAs($admin)
        ->delete("/integrations/{$app->id}")
        ->assertRedirect('/integrations');

    expect(IntegrationApp::find($app->id))->toBeNull();
    expect(IntegrationToken::where('integration_app_id', $app->id)->count())->toBe(0);
});

it('forbids regular users from deleting', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($user)
        ->delete("/integrations/{$app->id}")
        ->assertForbidden();

    expect(IntegrationApp::find($app->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Crafted Create — LanBrackets
|--------------------------------------------------------------------------
*/

it('displays the LanBrackets create page for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/integrations/create/lanbrackets')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('integrations/CreateLanBrackets'));
});

it('displays the LanShout create page for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/integrations/create/lanshout')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('integrations/CreateLanShout'));
});

it('displays the LanHelp create page for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/integrations/create/lanhelp')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('integrations/CreateLanHelp'));
});

it('creates a LanBrackets integration with prepopulated data', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanBrackets',
            'slug' => 'lanbrackets',
            'description' => 'Tournament bracket management system',
            'callback_url' => 'http://localhost:81/auth/callback',
            'nav_url' => 'http://localhost:81',
            'nav_label' => 'Brackets',
            'nav_icon' => 'swords',
            'allowed_scopes' => ['user:read', 'user:email', 'user:roles'],
            'is_active' => true,
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanbrackets')->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('LanBrackets');
    expect($app->nav_label)->toBe('Brackets');
    expect($app->nav_icon)->toBe('swords');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:email', 'user:roles']);
});

it('creates a LanShout integration with prepopulated data', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout',
            'slug' => 'lanshout',
            'description' => 'Real-time chat and communication platform',
            'callback_url' => 'http://localhost:82/auth/lancore/callback',
            'nav_url' => 'http://localhost:82',
            'nav_label' => 'Shout',
            'nav_icon' => 'megaphone',
            'allowed_scopes' => ['user:read', 'user:email', 'user:roles'],
            'is_active' => true,
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanshout')->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('LanShout');
    expect($app->nav_label)->toBe('Shout');
    expect($app->nav_icon)->toBe('megaphone');
});

it('creates a LanHelp integration with prepopulated data', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanHelp',
            'slug' => 'lanhelp',
            'description' => 'Help desk and support ticket system',
            'callback_url' => 'http://localhost:83/auth/lancore/callback',
            'nav_url' => 'http://localhost:83',
            'nav_label' => 'Help',
            'nav_icon' => 'life-buoy',
            'allowed_scopes' => ['user:read', 'user:email', 'user:roles'],
            'is_active' => true,
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanhelp')->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('LanHelp');
    expect($app->nav_label)->toBe('Help');
    expect($app->nav_icon)->toBe('life-buoy');
});
