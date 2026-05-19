<?php

use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('passes a presence map keyed by user id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $u1 = User::factory()->withRole(RoleName::User)->create();
    $u2 = User::factory()->withRole(RoleName::User)->create();

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('bulkStatusFor')
        ->once()
        ->andReturn([
            $admin->id => PresenceStatus::Active,
            $u1->id => PresenceStatus::Idle,
            $u2->id => PresenceStatus::Offline,
        ]);
    // statusFor is still called by the Inertia shared prop for the acting user.
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Active);
    $tracker->shouldReceive('touch')->andReturnNull();
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->actingAs($admin)
        ->get('/backstage/users')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->has('presence')
                ->where("presence.{$admin->id}", 'active')
                ->where("presence.{$u1->id}", 'idle')
                ->where("presence.{$u2->id}", 'offline')
                ->etc(),
        );
});

it('sorts users by presence status ascending (active → idle → offline)', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['name' => 'Zed Admin']);
    $offlineUser = User::factory()->withRole(RoleName::User)->create(['name' => 'Offline Olive']);
    $idleUser = User::factory()->withRole(RoleName::User)->create(['name' => 'Idle Ivan']);
    $activeUser = User::factory()->withRole(RoleName::User)->create(['name' => 'Active Alice']);

    $statuses = [
        $admin->id => PresenceStatus::Offline,
        $offlineUser->id => PresenceStatus::Offline,
        $idleUser->id => PresenceStatus::Idle,
        $activeUser->id => PresenceStatus::Active,
    ];

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('bulkStatusFor')->andReturnUsing(
        fn (iterable $ids) => collect($ids)
            ->mapWithKeys(fn ($id) => [(string) $id => $statuses[(string) $id] ?? PresenceStatus::Offline])
            ->all()
    );
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Active);
    $tracker->shouldReceive('touch')->andReturnNull();
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->actingAs($admin)
        ->get('/backstage/users?sort=presence&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->where('users.data.0.id', $activeUser->id)
                ->where('users.data.1.id', $idleUser->id)
                ->etc(),
        );
});

it('sorts users by presence status descending (offline → idle → active)', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $offlineUser = User::factory()->withRole(RoleName::User)->create();
    $activeUser = User::factory()->withRole(RoleName::User)->create();

    $statuses = [
        $admin->id => PresenceStatus::Active,
        $offlineUser->id => PresenceStatus::Offline,
        $activeUser->id => PresenceStatus::Active,
    ];

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('bulkStatusFor')->andReturnUsing(
        fn (iterable $ids) => collect($ids)
            ->mapWithKeys(fn ($id) => [(string) $id => $statuses[(string) $id] ?? PresenceStatus::Offline])
            ->all()
    );
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Active);
    $tracker->shouldReceive('touch')->andReturnNull();
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->actingAs($admin)
        ->get('/backstage/users?sort=presence&direction=desc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->where('users.data.0.id', $offlineUser->id)
                ->etc(),
        );
});

it('invokes bulkStatusFor exactly once per page render', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->count(15)->create();

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('bulkStatusFor')->once()->andReturn([]);
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Active);
    $tracker->shouldReceive('touch')->andReturnNull();
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->actingAs($admin)
        ->get('/backstage/users')
        ->assertSuccessful();
});
