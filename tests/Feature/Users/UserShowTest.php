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

it('allows admins to view the user show page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->get("/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Show')
                ->has('user')
                ->has('availableRoles')
                ->where('user.id', $user->id)
        );
});

it('allows superadmins to view the user show page', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($superadmin)
        ->get("/users/{$user->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/Show'));
});

it('forbids users from viewing the show page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get("/users/{$target->id}")
        ->assertForbidden();
});

it('allows admins to update a user name and email', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect();

    expect($user->fresh())
        ->name->toBe('Updated Name')
        ->email->toBe('updated@example.com');
});

it('allows admins to change a user password', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertRedirect();

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

it('allows superadmins to sync user roles', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($superadmin)
        ->patch("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role_names' => [RoleName::Admin->value],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->fresh()->roles->pluck('name')->toArray())
        ->toContain(RoleName::Admin);
});

it('validates required fields on update', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => '',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors(['name', 'email']);
});

it('prevents duplicate email on update', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();
    $other = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $other->email,
        ])
        ->assertSessionHasErrors(['email']);
});

it('forbids users from updating another user', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->patch("/users/{$target->id}", [
            'name' => 'Hacked',
            'email' => 'hacked@example.com',
        ])
        ->assertForbidden();
});

it('allows superadmins to sync sponsor_manager role', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($superadmin)
        ->patch("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role_names' => [RoleName::Admin->value, RoleName::SponsorManager->value],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $roleNames = $user->fresh()->roles->pluck('name')->all();

    expect($roleNames)
        ->toContain(RoleName::Admin)
        ->toContain(RoleName::SponsorManager);
});
