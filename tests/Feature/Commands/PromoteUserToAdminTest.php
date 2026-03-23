<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('promotes a user to admin by default', function () {
    $user = User::factory()->withRole(RoleName::User)->create(['name' => 'Jane Doe']);

    $this->artisan("users:promote {$user->email}")
        ->expectsOutputToContain("Promoted Jane Doe ({$user->email}) to 'admin'")
        ->assertSuccessful();

    expect($user->fresh()->hasRole(RoleName::Admin))->toBeTrue();
});

it('promotes a user to superadmin', function () {
    $user = User::factory()->withRole(RoleName::User)->create(['name' => 'Jane Doe']);

    $this->artisan("users:promote {$user->email} --role=superadmin")
        ->expectsOutputToContain("Promoted Jane Doe ({$user->email}) to 'superadmin'")
        ->assertSuccessful();

    expect($user->fresh()->hasRole(RoleName::Superadmin))->toBeTrue();
});

it('fails when user is not found', function () {
    $this->artisan('users:promote nonexistent@example.com')
        ->expectsOutputToContain('No user found with email: nonexistent@example.com')
        ->assertFailed();
});

it('fails when role is invalid', function () {
    $user = User::factory()->create();

    $this->artisan("users:promote {$user->email} --role=invalid")
        ->expectsOutputToContain("Invalid role. Use 'admin' or 'superadmin'.")
        ->assertFailed();
});

it('fails when role is user', function () {
    $user = User::factory()->create();

    $this->artisan("users:promote {$user->email} --role=user")
        ->expectsOutputToContain("Invalid role. Use 'admin' or 'superadmin'.")
        ->assertFailed();
});

it('reports when user already has the role', function () {
    $user = User::factory()->withRole(RoleName::Admin)->create(['name' => 'Jane Doe']);

    $this->artisan("users:promote {$user->email} --role=admin")
        ->expectsOutputToContain("Jane Doe ({$user->email}) already has the 'admin' role.")
        ->assertSuccessful();
});
