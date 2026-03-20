<?php

use App\Domain\Venue\Models\Venue;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('returns paginated venues for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Venue::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/venues')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('venues/Index')
                ->has('venues.data')
                ->has('venues.total')
                ->has('filters')
        );
});

it('filters venues by search term', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Venue::factory()->create(['name' => 'Main Arena']);
    Venue::factory()->create(['name' => 'Side Hall']);

    $this->actingAs($admin)
        ->get('/venues?search=arena')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('venues/Index')
                ->where('venues.total', 1)
                ->where('venues.data.0.name', 'Main Arena')
        );
});

it('sorts venues by name ascending', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Venue::factory()->create(['name' => 'Zeta Hall']);
    Venue::factory()->create(['name' => 'Alpha Arena']);

    $this->actingAs($admin)
        ->get('/venues?sort=name&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('venues/Index')
                ->where('venues.data.0.name', 'Alpha Arena')
        );
});
