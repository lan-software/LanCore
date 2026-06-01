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

it('grants Telescope access to superadmins', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    expect(Gate::forUser($superadmin)->allows('viewTelescope'))->toBeTrue();
});

it('denies Telescope access to admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    expect(Gate::forUser($admin)->allows('viewTelescope'))->toBeFalse();
});

it('denies Telescope access to moderators', function () {
    $moderator = User::factory()->withRole(RoleName::Moderator)->create();

    expect(Gate::forUser($moderator)->allows('viewTelescope'))->toBeFalse();
});

it('denies Telescope access to regular users', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    expect(Gate::forUser($user)->allows('viewTelescope'))->toBeFalse();
});

it('denies Telescope access to guests', function () {
    expect(Gate::allows('viewTelescope'))->toBeFalse();
});
