<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('redirects unauthenticated users to login', function () {
    $this->get('/users')
        ->assertRedirectToRoute('login');
});

it('forbids users with the user role', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertForbidden();
});

it('allows users with the admin role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/users')
        ->assertSuccessful();
});

it('allows users with the superadmin role', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->get('/users')
        ->assertSuccessful();
});
