<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Role::updateOrCreate(['name' => RoleName::SponsorManager->value], ['label' => 'Sponsor Manager']);
});

it('redirects unauthenticated users to login', function () {
    $this->get('/sponsors')
        ->assertRedirectToRoute('login');
});

it('forbids users with the user role', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/sponsors')
        ->assertForbidden();
});

it('allows users with the admin role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/sponsors')
        ->assertSuccessful();
});

it('allows users with the superadmin role', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->get('/sponsors')
        ->assertSuccessful();
});

it('allows users with the sponsor_manager role', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();

    $this->actingAs($manager)
        ->get('/sponsors')
        ->assertSuccessful();
});

it('forbids sponsor managers from accessing create page', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();

    $this->actingAs($manager)
        ->get('/sponsors/create')
        ->assertForbidden();
});

it('forbids sponsor managers from accessing sponsor-levels', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();

    $this->actingAs($manager)
        ->get('/sponsor-levels')
        ->assertForbidden();
});
