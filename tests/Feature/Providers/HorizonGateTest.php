<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('grants Horizon access to superadmins', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    expect(Gate::forUser($superadmin)->allows('viewHorizon'))->toBeTrue();
});

it('denies Horizon access to admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeFalse();
});

it('denies Horizon access to regular users', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
});

it('denies Horizon access to guests', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse();
});
