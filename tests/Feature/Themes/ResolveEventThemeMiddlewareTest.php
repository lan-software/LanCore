<?php

use App\Domain\Event\Models\Event;
use App\Domain\Theme\Models\Theme;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Cache::forget('inertia.activeTheme.default_id');
});

it('shares the active palette on event-scoped routes', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->create([
        'name' => 'Brand',
        'light_config' => ['--primary' => '#0a246a'],
        'dark_config' => ['--primary' => '#1d4ed8'],
    ]);
    $event = Event::factory()->create(['theme_id' => $theme->id]);

    $this->actingAs($admin)
        ->get("/backstage/events/{$event->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeTheme.name', 'Brand')
            ->where('activeTheme.source', 'event')
            ->where('activeTheme.lightConfig.--primary', '#0a246a')
            ->where('activeTheme.darkConfig.--primary', '#1d4ed8')
            ->etc(),
        );
});

it('falls back to the organization default theme when no event theme is assigned', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $defaultTheme = Theme::factory()->create([
        'name' => 'Org Default',
        'light_config' => ['--accent' => '#abcdef'],
    ]);
    OrganizationSetting::set('default_theme_id', $defaultTheme->id);
    Cache::forget('inertia.activeTheme.default_id');

    $event = Event::factory()->create(['theme_id' => null]);

    $this->actingAs($admin)
        ->get("/backstage/events/{$event->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeTheme.name', 'Org Default')
            ->where('activeTheme.source', 'organization')
            ->etc(),
        );
});

it('uses the organization default on routes without an event in scope', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $defaultTheme = Theme::factory()->create([
        'name' => 'Org Default',
    ]);
    OrganizationSetting::set('default_theme_id', $defaultTheme->id);
    Cache::forget('inertia.activeTheme.default_id');

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeTheme.source', 'organization')
            ->etc(),
        );
});

it('returns null activeTheme when no event theme and no org default are set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeTheme', null)
            ->etc(),
        );
});

it('caches the resolved default theme id so it is not queried on every request', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    // No org default configured: the resolver must still cache a sentinel so
    // Cache::remember() stops re-running its callback (and querying) per request.
    expect(Cache::has('inertia.activeTheme.default_id'))->toBeFalse();

    $this->actingAs($admin)->get('/dashboard')->assertSuccessful();

    expect(Cache::has('inertia.activeTheme.default_id'))->toBeTrue();

    // OrganizationSetting::get() must not be hit again while the value is cached.
    $queries = 0;
    DB::listen(function ($query) use (&$queries) {
        if (str_contains($query->sql, 'organization_settings')) {
            $queries++;
        }
    });

    $this->actingAs($admin)->get('/dashboard')->assertSuccessful();

    expect($queries)->toBe(0);
});

it('caches the configured default theme id and reuses it across requests', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $defaultTheme = Theme::factory()->create(['name' => 'Cached Default']);
    OrganizationSetting::set('default_theme_id', $defaultTheme->id);
    Cache::forget('inertia.activeTheme.default_id');

    $this->actingAs($admin)->get('/dashboard')->assertSuccessful();

    expect(Cache::get('inertia.activeTheme.default_id'))->toBe($defaultTheme->id);
});

it('per-event theme overrides the organization default', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $defaultTheme = Theme::factory()->create(['name' => 'Org Default']);
    $eventTheme = Theme::factory()->create(['name' => 'Event Theme']);
    OrganizationSetting::set('default_theme_id', $defaultTheme->id);
    Cache::forget('inertia.activeTheme.default_id');

    $event = Event::factory()->create(['theme_id' => $eventTheme->id]);

    $this->actingAs($admin)
        ->get("/backstage/events/{$event->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('activeTheme.name', 'Event Theme')
            ->where('activeTheme.source', 'event')
            ->etc(),
        );
});
