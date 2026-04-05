<?php

use App\Domain\Announcement\Enums\Permission as AnnouncementPermission;
use App\Domain\News\Enums\Permission as NewsPermission;
use App\Domain\Sponsoring\Enums\Permission as SponsoringPermission;
use App\Enums\Permission;
use App\Enums\RoleName;
use App\Enums\RolePermissionMap;

it('grants superadmin all permissions', function () {
    $permissions = RolePermissionMap::forRole(RoleName::Superadmin);

    expect($permissions)->toEqual(RolePermissionMap::all());
});

it('excludes SyncUserRoles and DeleteUsers from admin', function () {
    $permissions = RolePermissionMap::forRole(RoleName::Admin);

    expect($permissions)->not->toContain(Permission::SyncUserRoles)
        ->and($permissions)->not->toContain(Permission::DeleteUsers);
});

it('gives admin all other permissions except superadmin-only and sponsor-manager-only', function () {
    $adminPerms = RolePermissionMap::forRole(RoleName::Admin);
    $excluded = [Permission::SyncUserRoles, Permission::DeleteUsers, SponsoringPermission::ManageAssignedSponsors];

    $expected = array_filter(
        RolePermissionMap::all(),
        fn ($p) => ! in_array($p, $excluded, true),
    );

    foreach ($expected as $perm) {
        expect($adminPerms)->toContain($perm);
    }
});

it('gives moderator only content moderation permissions', function () {
    $permissions = RolePermissionMap::forRole(RoleName::Moderator);

    expect($permissions)->toContain(NewsPermission::ModerateNewsComments)
        ->and($permissions)->toContain(AnnouncementPermission::ManageAnnouncements)
        ->and($permissions)->toHaveCount(2);
});

it('gives sponsor manager only assigned sponsors permission', function () {
    $permissions = RolePermissionMap::forRole(RoleName::SponsorManager);

    expect($permissions)->toEqual([SponsoringPermission::ManageAssignedSponsors]);
});

it('gives regular user no permissions', function () {
    $permissions = RolePermissionMap::forRole(RoleName::User);

    expect($permissions)->toBeEmpty();
});

it('covers every RoleName case', function () {
    foreach (RoleName::cases() as $role) {
        $result = RolePermissionMap::forRole($role);
        expect($result)->toBeArray();
    }
});
