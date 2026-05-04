<?php

use App\Domain\Theme\Models\Theme;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Cache::forget('inertia.activeTheme.default_id');
});

it('admin sets the site-wide default theme', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($admin)
        ->patch('/themes/default', ['theme_id' => $theme->id])
        ->assertRedirect();

    expect(OrganizationSetting::get('default_theme_id'))->toBe($theme->id);
});

it('admin clears the site-wide default theme by passing null', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();
    OrganizationSetting::set('default_theme_id', $theme->id);

    $this->actingAs($admin)
        ->patch('/themes/default', ['theme_id' => null])
        ->assertRedirect();

    expect(OrganizationSetting::get('default_theme_id'))->toBeNull();
});

it('rejects setting the default to a non-existent theme', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->patch('/themes/default', ['theme_id' => 999_999])
        ->assertSessionHasErrors(['theme_id']);
});

it('exposes the current default theme on the index page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();
    OrganizationSetting::set('default_theme_id', $theme->id);

    $this->actingAs($admin)
        ->get('/themes')
        ->assertInertia(fn ($page) => $page
            ->component('themes/Index')
            ->where('defaultThemeId', $theme->id)
            ->etc(),
        );
});
