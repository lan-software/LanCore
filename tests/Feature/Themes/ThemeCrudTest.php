<?php

use App\Domain\Theme\Models\Theme;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('lists themes for an admin', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Theme::factory()->withPalette()->create(['name' => 'Retro Classic']);

    $this->actingAs($admin)
        ->get('/themes')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('themes/Index')
            ->has('themes', 1)
            ->where('themes.0.name', 'Retro Classic')
        );
});

it('shows the create theme page with the palette schema', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/themes/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('themes/Create')
            ->has('paletteVariables')
        );
});

it('stores a palette theme with light + dark configs', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Brand Palette',
            'description' => 'Brand colors',
            'light_config' => ['--primary' => '#0a246a', '--accent' => '#f0c419'],
            'dark_config' => ['--primary' => '#1d4ed8'],
        ])
        ->assertRedirect('/themes');

    $theme = Theme::where('name', 'Brand Palette')->first();
    expect($theme)->not->toBeNull()
        ->and($theme->description)->toBe('Brand colors')
        ->and($theme->light_config)->toBe(['--primary' => '#0a246a', '--accent' => '#f0c419'])
        ->and($theme->dark_config)->toBe(['--primary' => '#1d4ed8']);
});

it('stores a theme with no palette overrides', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Minimal',
        ])
        ->assertRedirect('/themes');

    expect(Theme::where('name', 'Minimal')->first())
        ->light_config->toBeNull()
        ->dark_config->toBeNull();
});

it('rejects palette keys not in the curated allowlist', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Sneaky',
            'light_config' => ['--something-not-allowed' => '#000'],
        ])
        ->assertSessionHasErrors(['light_config']);
});

it('rejects palette keys that do not look like CSS custom properties', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Injection Theme',
            'light_config' => ['primary' => '#000'],
        ])
        ->assertSessionHasErrors(['light_config']);
});

it('rejects palette values that contain css-injection characters', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Injection Theme',
            'light_config' => ['--primary' => 'red; } </style><script>'],
        ])
        ->assertSessionHasErrors();
});

it('enforces unique theme names', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Theme::factory()->withPalette()->create(['name' => 'Retro Classic']);

    $this->actingAs($admin)
        ->post('/themes', [
            'name' => 'Retro Classic',
        ])
        ->assertSessionHasErrors(['name']);
});

it('updates an existing theme', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch("/themes/{$theme->id}", [
            'name' => 'New Name',
            'light_config' => ['--accent' => '#abcdef'],
        ])
        ->assertRedirect('/themes');

    expect($theme->fresh())
        ->name->toBe('New Name')
        ->light_config->toBe(['--accent' => '#abcdef']);
});

it('deletes a theme', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($admin)
        ->delete("/themes/{$theme->id}")
        ->assertRedirect('/themes');

    expect(Theme::find($theme->id))->toBeNull();
});
