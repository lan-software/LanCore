<?php

use App\Enums\Permission;
use App\Enums\RoleName;

it('grants superadmin all permissions', function () {
    $permissions = Permission::forRole(RoleName::Superadmin);

    expect($permissions)->toEqual(Permission::cases());
});

it('excludes SyncUserRoles and DeleteUsers from admin', function () {
    $permissions = Permission::forRole(RoleName::Admin);

    expect($permissions)->not->toContain(Permission::SyncUserRoles)
        ->and($permissions)->not->toContain(Permission::DeleteUsers);
});

it('gives admin all other permissions', function () {
    $adminPerms = Permission::forRole(RoleName::Admin);
    $expected = array_filter(
        Permission::cases(),
        fn (Permission $p) => ! in_array($p, [Permission::SyncUserRoles, Permission::DeleteUsers, Permission::ManageAssignedSponsors], true),
    );

    foreach ($expected as $perm) {
        expect($adminPerms)->toContain($perm);
    }
});

it('gives moderator only content moderation permissions', function () {
    $permissions = Permission::forRole(RoleName::Moderator);

    expect($permissions)->toContain(Permission::ModerateNewsComments)
        ->and($permissions)->toContain(Permission::ManageAnnouncements)
        ->and($permissions)->toHaveCount(2);
});

it('gives sponsor manager only assigned sponsors permission', function () {
    $permissions = Permission::forRole(RoleName::SponsorManager);

    expect($permissions)->toEqual([Permission::ManageAssignedSponsors]);
});

it('gives regular user no permissions', function () {
    $permissions = Permission::forRole(RoleName::User);

    expect($permissions)->toBeEmpty();
});

it('covers every RoleName case', function () {
    foreach (RoleName::cases() as $role) {
        $result = Permission::forRole($role);
        expect($result)->toBeArray();
    }
});
