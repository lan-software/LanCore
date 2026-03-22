<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('lists all users in a table', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['name' => 'Alice Admin']);
    $user = User::factory()->withRole(RoleName::User)->create(['name' => 'Bob User']);

    $this->artisan('users:list')
        ->expectsTable(
            ['ID', 'Name', 'Email', 'Roles', 'Verified'],
            [
                [$admin->id, 'Alice Admin', $admin->email, 'admin', 'Yes'],
                [$user->id, 'Bob User', $user->email, 'user', 'Yes'],
            ],
        )
        ->assertSuccessful();
});

it('filters users by role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['name' => 'Alice Admin']);
    User::factory()->withRole(RoleName::User)->create(['name' => 'Bob User']);

    $this->artisan('users:list --role=admin')
        ->expectsTable(
            ['ID', 'Name', 'Email', 'Roles', 'Verified'],
            [
                [$admin->id, 'Alice Admin', $admin->email, 'admin', 'Yes'],
            ],
        )
        ->assertSuccessful();
});

it('shows error for invalid role', function () {
    $this->artisan('users:list --role=invalid')
        ->expectsOutputToContain("Invalid role 'invalid'")
        ->assertFailed();
});

it('shows message when no users are found', function () {
    $this->artisan('users:list')
        ->expectsOutputToContain('No users found.')
        ->assertSuccessful();
});

it('shows unverified status for unverified users', function () {
    $user = User::factory()->unverified()->create(['name' => 'Unverified User']);

    $this->artisan('users:list')
        ->expectsTable(
            ['ID', 'Name', 'Email', 'Roles', 'Verified'],
            [
                [$user->id, 'Unverified User', $user->email, '', 'No'],
            ],
        )
        ->assertSuccessful();
});
