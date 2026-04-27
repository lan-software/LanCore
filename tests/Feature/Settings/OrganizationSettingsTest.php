<?php

use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Cache::forget('inertia.organization');
});

it('shares the organization Inertia prop with null logoUrl when no logo is uploaded', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    OrganizationSetting::set('name', 'Acme LAN');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('organization.name', 'Acme LAN')
                ->where('organization.logoUrl', null)
        );
});

it('shares the organization Inertia prop with a logoUrl when a logo is uploaded', function () {
    Storage::fake('public');
    $user = User::factory()->withRole(RoleName::User)->create();
    OrganizationSetting::set('name', 'Acme LAN');
    OrganizationSetting::set('logo', 'organization/logo.png');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('organization.name', 'Acme LAN')
                ->where('organization.logoUrl', StorageRole::publicUrl('organization/logo.png'))
        );
});

it('caches the organization Inertia prop after the first request', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    expect(Cache::has('inertia.organization'))->toBeFalse();

    $this->actingAs($user)->get('/dashboard')->assertSuccessful();

    expect(Cache::has('inertia.organization'))->toBeTrue();
});

it('forgets the organization cache when settings are updated', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Cache::put('inertia.organization', ['name' => 'stale'], 3600);

    $this->actingAs($admin)
        ->patch('/organization-settings', ['name' => 'New Name'])
        ->assertRedirect();

    expect(Cache::has('inertia.organization'))->toBeFalse();
    expect(OrganizationSetting::get('name'))->toBe('New Name');
});

it('forgets the organization cache and stores the file when a logo is uploaded', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Cache::put('inertia.organization', ['name' => 'stale'], 3600);

    $this->actingAs($admin)
        ->post('/organization-settings/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])
        ->assertRedirect();

    expect(Cache::has('inertia.organization'))->toBeFalse();
    $path = OrganizationSetting::get('logo');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('forgets the organization cache and removes the logo file when the logo is removed', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $file = UploadedFile::fake()->image('logo.png');
    $path = $file->store('organization', 'public');
    OrganizationSetting::set('logo', $path);
    Cache::put('inertia.organization', ['name' => 'stale'], 3600);

    $this->actingAs($admin)
        ->delete('/organization-settings/logo')
        ->assertRedirect();

    expect(Cache::has('inertia.organization'))->toBeFalse();
    expect(OrganizationSetting::get('logo'))->toBeNull();
});
