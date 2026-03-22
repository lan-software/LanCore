<?php

use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a sponsor level', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sponsorLevel = SponsorLevel::factory()->create();

    $this->actingAs($admin)
        ->get("/sponsor-levels/{$sponsorLevel->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsor-levels/Audit')
                ->has('sponsorLevel')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the sponsor level audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $sponsorLevel = SponsorLevel::factory()->create();

    $this->actingAs($user)
        ->get("/sponsor-levels/{$sponsorLevel->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for sponsor level audit', function () {
    $sponsorLevel = SponsorLevel::factory()->create();

    $this->get("/sponsor-levels/{$sponsorLevel->id}/audit")
        ->assertRedirect('/login');
});
