<?php

use App\Domain\Ticketing\Models\Addon;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a ticket addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->get("/ticket-addons/{$addon->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Audit')
                ->has('addon')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the ticket addon audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($user)
        ->get("/ticket-addons/{$addon->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for ticket addon audit', function () {
    $addon = Addon::factory()->create();

    $this->get("/ticket-addons/{$addon->id}/audit")
        ->assertRedirect('/login');
});
