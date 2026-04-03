<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);

    Route::middleware(['web', 'role:admin,superadmin'])->get('/_test/role-check', fn () => 'OK');
});

it('allows a user with the required role through a route', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/_test/role-check')
        ->assertOk()
        ->assertSee('OK');
});

it('allows a superadmin through a route requiring admin or superadmin', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->get('/_test/role-check')
        ->assertOk();
});

it('denies a user without the required role', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/_test/role-check')
        ->assertForbidden();
});

it('denies unauthenticated requests', function () {
    $this->get('/_test/role-check')
        ->assertForbidden();
});
