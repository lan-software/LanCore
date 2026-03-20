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

it('allows admins to view the create venue page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/venues/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('venues/Create'));
});

it('allows admins to store a new venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [
            'name' => 'Test Venue',
            'description' => 'A great venue',
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip_code' => '12345',
            'country' => 'US',
        ])
        ->assertRedirect('/venues');

    expect(Venue::where('name', 'Test Venue')->exists())->toBeTrue();
});

it('validates required fields when storing a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [])
        ->assertSessionHasErrors(['name', 'street', 'city', 'zip_code', 'country']);
});

it('allows admins to view the edit venue page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->get("/venues/{$venue->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('venues/Edit')
                ->has('venue')
                ->where('venue.id', $venue->id)
        );
});

it('allows admins to update a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}", [
            'name' => 'Updated Venue',
            'street' => '456 Elm St',
            'city' => 'Shelbyville',
            'zip_code' => '67890',
            'country' => 'US',
        ])
        ->assertRedirect();

    expect($venue->fresh()->name)->toBe('Updated Venue');
});

it('allows admins to delete a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->delete("/venues/{$venue->id}")
        ->assertRedirect('/venues');

    expect(Venue::find($venue->id))->toBeNull();
});

it('forbids users from creating venues', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/venues', [
            'name' => 'Test',
            'street' => '1 St',
            'city' => 'City',
            'zip_code' => '00000',
            'country' => 'US',
        ])
        ->assertForbidden();
});
