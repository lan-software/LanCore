<?php

use App\Actions\User\ChangeRoles;
use App\Domain\Notification\Events\UserRolesChanged;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Role::updateOrCreate(['name' => RoleName::SponsorManager->value], ['label' => 'Sponsor Manager']);
});

it('dispatches UserRolesChanged when assigning a new role', function () {
    Event::fake([UserRolesChanged::class]);

    $user = User::factory()->withRole(RoleName::User)->create();

    app(ChangeRoles::class)->assign($user, RoleName::Admin);

    Event::assertDispatched(UserRolesChanged::class, function (UserRolesChanged $event) use ($user) {
        return $event->user->is($user)
            && $event->addedRoles === [RoleName::Admin]
            && $event->removedRoles === [];
    });
});

it('does not dispatch UserRolesChanged when user already has the role', function () {
    Event::fake([UserRolesChanged::class]);

    $user = User::factory()->withRole(RoleName::Admin)->create();

    app(ChangeRoles::class)->assign($user, RoleName::Admin);

    Event::assertNotDispatched(UserRolesChanged::class);
});

it('dispatches UserRolesChanged for each user in bulk assign', function () {
    Event::fake([UserRolesChanged::class]);

    $users = User::factory()->withRole(RoleName::User)->count(3)->create();

    app(ChangeRoles::class)->assignBulk($users, RoleName::Admin);

    Event::assertDispatchedTimes(UserRolesChanged::class, 3);
});

it('dispatches UserRolesChanged with correct diff when syncing roles', function () {
    Event::fake([UserRolesChanged::class]);

    $user = User::factory()->withRole(RoleName::User)->create();

    app(ChangeRoles::class)->sync($user, RoleName::Admin, RoleName::SponsorManager);

    Event::assertDispatched(UserRolesChanged::class, function (UserRolesChanged $event) use ($user) {
        return $event->user->is($user)
            && in_array(RoleName::Admin, $event->addedRoles)
            && in_array(RoleName::SponsorManager, $event->addedRoles)
            && in_array(RoleName::User, $event->removedRoles);
    });
});

it('does not dispatch UserRolesChanged when sync results in no changes', function () {
    Event::fake([UserRolesChanged::class]);

    $user = User::factory()->withRole(RoleName::Admin)->create();

    app(ChangeRoles::class)->sync($user, RoleName::Admin);

    Event::assertNotDispatched(UserRolesChanged::class);
});
