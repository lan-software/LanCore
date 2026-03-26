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
    $this->get('/integrations')
        ->assertRedirectToRoute('login');
});

it('forbids users with the user role', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/integrations')
        ->assertForbidden();
});

it('allows users with the admin role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/integrations')
        ->assertSuccessful();
});

it('allows users with the superadmin role', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->get('/integrations')
        ->assertSuccessful();
});

it('forbids regular users from accessing create page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/integrations/create')
        ->assertForbidden();
});

it('allows admins to access create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/integrations/create')
        ->assertSuccessful();
});
