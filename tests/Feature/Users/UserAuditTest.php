<?php

use App\Actions\User\ChangeRoles;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('records an audit row when a user is updated', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    $audit = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->new_values)->toHaveKey('name', 'Updated Name')
        ->and($audit->user_id)->toBe($admin->id);
});

it('never persists secrets in audit values', function () {
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

    $audits = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->get();

    foreach ($audits as $audit) {
        expect($audit->old_values)->not->toHaveKey('password')
            ->and($audit->new_values)->not->toHaveKey('password')
            ->and($audit->new_values)->not->toHaveKey('remember_token')
            ->and($audit->new_values)->not->toHaveKey('two_factor_secret');
    }
});

it('records a custom audit row when roles change via sync', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin);

    app(ChangeRoles::class)->sync($user, RoleName::Admin);

    $audit = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'roles_synced')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->old_values['roles'])->toContain(RoleName::User->value)
        ->and($audit->new_values['roles'])->toContain(RoleName::Admin->value)
        ->and($audit->user_id)->toBe($admin->id);
});

it('does not record a role audit row when sync is a no-op', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin);

    app(ChangeRoles::class)->sync($user, RoleName::User);

    $audit = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'roles_synced')
        ->first();

    expect($audit)->toBeNull();
});

it('exposes the on-user audit endpoint to admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$user->id}", [
            'name' => 'Auditable Name',
            'email' => $user->email,
        ]);

    $this->actingAs($admin)
        ->get("/users/{$user->id}/audit/on")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Audit')
                ->where('perspective', 'on')
                ->where('user.id', $user->id)
                ->has('audits.data.0')
        );
});

it('exposes the by-user audit endpoint to admins and lists actor changes', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($admin)
        ->patch("/users/{$target->id}", [
            'name' => 'Driven By Admin',
            'email' => $target->email,
        ]);

    $this->actingAs($admin)
        ->get("/users/{$admin->id}/audit/by")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('users/Audit')
                ->where('perspective', 'by')
                ->where('user.id', $admin->id)
                ->has('audits.data.0')
        );
});

it('forbids non-admins from viewing the user audit endpoints', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $target = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get("/users/{$target->id}/audit/on")
        ->assertForbidden();

    $this->actingAs($user)
        ->get("/users/{$target->id}/audit/by")
        ->assertForbidden();
});
