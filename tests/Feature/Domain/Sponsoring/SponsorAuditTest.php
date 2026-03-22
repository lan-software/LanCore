<?php

use App\Domain\Sponsoring\Models\Sponsor;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a sponsor', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($admin)
        ->get("/sponsors/{$sponsor->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsors/Audit')
                ->has('sponsor')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the sponsor audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($user)
        ->get("/sponsors/{$sponsor->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for sponsor audit', function () {
    $sponsor = Sponsor::factory()->create();

    $this->get("/sponsors/{$sponsor->id}/audit")
        ->assertRedirect('/login');
});
