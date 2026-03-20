<?php

use App\Domain\Event\Models\Event;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Venue\Models\Venue;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('non-admin users see dashboard without stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('isAdmin', false)
                ->where('stats', [])
        );
});

test('admin users see dashboard with stats', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('isAdmin', true)
                ->has('stats.counts')
                ->has('stats.events')
                ->has('stats.roles')
                ->has('stats.lastActiveUsers')
        );
});

test('admin dashboard shows correct entity counts', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    User::factory()->count(3)->create();
    $venue = Venue::factory()->create();
    Event::factory()->count(2)->for($venue)->create();
    $level = SponsorLevel::factory()->create();
    Sponsor::factory()->count(2)->for($level, 'sponsorLevel')->create();

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.counts.users', 4)
                ->where('stats.counts.events', 2)
                ->where('stats.counts.venues', 1)
                ->where('stats.counts.sponsors', 2)
                ->where('stats.counts.sponsor_levels', 1)
        );
});

test('admin dashboard shows event breakdown', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);
    Event::factory()->create([
        'start_date' => now()->subDays(3),
        'end_date' => now()->subDays(1),
    ]);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.events.upcoming', 1)
                ->where('stats.events.past', 1)
                ->where('stats.events.published', 1)
                ->where('stats.events.draft', 1)
        );
});

test('admin dashboard shows role distribution', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $sponsorManagerRole = Role::factory()->create(['name' => RoleName::SponsorManager]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $manager = User::factory()->create();
    $manager->roles()->attach($sponsorManagerRole);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.roles.admin', 1)
                ->where('stats.roles.sponsor_manager', 1)
        );
});
