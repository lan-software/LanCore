<?php

use App\Contracts\PermissionEnum;
use App\Domain\Announcement\Enums\Permission as AnnouncementPermission;
use App\Domain\News\Enums\Permission as NewsPermission;
use App\Domain\Sponsoring\Enums\Permission as SponsoringPermission;
use App\Domain\Venue\Enums\Permission as VenuePermission;
use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Moderator->value], ['label' => 'Moderator']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Role::updateOrCreate(['name' => RoleName::SponsorManager->value], ['label' => 'Sponsor Manager']);
});

it('returns true for a permission the user role grants', function () {
    $user = User::factory()->withRole(RoleName::Admin)->create();

    expect($user->hasPermission(Permission::ManageUsers))->toBeTrue();
});

it('returns false for a permission the user role does not grant', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    expect($user->hasPermission(Permission::ManageUsers))->toBeFalse();
});

it('checks hasAnyPermission correctly', function () {
    $user = User::factory()->withRole(RoleName::Moderator)->create();

    expect($user->hasAnyPermission(Permission::ManageUsers, NewsPermission::ModerateNewsComments))->toBeTrue()
        ->and($user->hasAnyPermission(Permission::ManageUsers, VenuePermission::ManageVenues))->toBeFalse();
});

it('collects all permissions from multiple roles', function () {
    $user = User::factory()->withRole(RoleName::Moderator)->create();
    $sponsorManagerRole = Role::where('name', RoleName::SponsorManager)->first();
    $user->roles()->attach($sponsorManagerRole);
    $user->load('roles');

    $allPerms = $user->allPermissions();

    expect($allPerms)->toContain(NewsPermission::ModerateNewsComments)
        ->and($allPerms)->toContain(AnnouncementPermission::ManageAnnouncements)
        ->and($allPerms)->toContain(SponsoringPermission::ManageAssignedSponsors)
        ->and($allPerms)->toHaveCount(3);
});

it('deduplicates permissions from overlapping roles', function () {
    $user = User::factory()->withRole(RoleName::Admin)->create();
    $moderatorRole = Role::where('name', RoleName::Moderator)->first();
    $user->roles()->attach($moderatorRole);
    $user->load('roles');

    $allPerms = $user->allPermissions();
    $values = array_map(fn (PermissionEnum $p) => $p->value, $allPerms);

    expect(count($values))->toBe(count(array_unique($values)));
});
