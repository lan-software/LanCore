<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('returns paginated users with their roles for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->count(3)->create();

    $this->actingAs($admin)
        ->get('/users')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->has('users.data')
                ->has('users.total')
                ->has('filters')
        );
});

it('forbids the user role from accessing the user list', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/users')
        ->assertForbidden();
});

it('filters users by search term', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->create(['name' => 'Alice Wonderland', 'email' => 'alice@example.com']);
    User::factory()->withRole(RoleName::User)->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $this->actingAs($admin)
        ->get('/users?search=alice')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Alice Wonderland')
        );
});

it('filters users by role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($admin)
        ->get('/users?role=user')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->where('users.total', 2)
        );
});

it('sorts users by email ascending', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    User::factory()->withRole(RoleName::User)->create(['email' => 'zzz@example.com']);
    User::factory()->withRole(RoleName::User)->create(['email' => 'aaa@example.com']);

    $this->actingAs($admin)
        ->get('/users?sort=email&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Index')
                ->where('users.data.0.email', 'aaa@example.com')
        );
});

it('allows superadmin to bulk delete users', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $users = User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($superadmin)
        ->delete('/users', ['ids' => $users->pluck('id')->toArray()])
        ->assertRedirect();

    expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
});

it('prevents admins from bulk deleting users', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $users = User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($admin)
        ->delete('/users', ['ids' => $users->pluck('id')->toArray()])
        ->assertForbidden();
});

it('prevents superadmin from deleting their own account in bulk', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->delete('/users', ['ids' => [$superadmin->id]])
        ->assertRedirect();

    expect(User::find($superadmin->id))->not->toBeNull();
});

it('allows admins to bulk assign a role to users', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $users = User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($admin)
        ->patch('/users/roles', [
            'ids' => $users->pluck('id')->toArray(),
            'role' => RoleName::Admin->value,
        ])
        ->assertRedirect();

    foreach ($users as $user) {
        expect($user->fresh()->hasRole(RoleName::Admin))->toBeTrue();
    }
});

it('allows superadmin to bulk assign a role', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $users = User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($superadmin)
        ->patch('/users/roles', [
            'ids' => $users->pluck('id')->toArray(),
            'role' => RoleName::Admin->value,
        ])
        ->assertRedirect();

    foreach ($users as $user) {
        expect($user->fresh()->hasRole(RoleName::Admin))->toBeTrue();
    }
});

it('prevents the user role from bulk assigning roles', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $targets = User::factory()->withRole(RoleName::User)->count(2)->create();

    $this->actingAs($user)
        ->patch('/users/roles', [
            'ids' => $targets->pluck('id')->toArray(),
            'role' => RoleName::Admin->value,
        ])
        ->assertForbidden();
});

it('does not duplicate role when bulk assigning an existing role', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    // Assign twice
    $this->actingAs($admin)->patch('/users/roles', ['ids' => [$target->id], 'role' => RoleName::Admin->value]);
    $this->actingAs($admin)->patch('/users/roles', ['ids' => [$target->id], 'role' => RoleName::Admin->value]);

    expect($target->fresh()->roles()->where('name', RoleName::Admin->value)->count())->toBe(1);
});
