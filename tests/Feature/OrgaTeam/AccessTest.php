<?php

use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('redirects unauthenticated users from the orga-teams index', function () {
    $this->get('/backstage/orga-teams')->assertRedirectToRoute('login');
});

it('forbids users with the user role', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)->get('/backstage/orga-teams')->assertForbidden();
});

it('allows admins to view the index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)->get('/backstage/orga-teams')->assertSuccessful();
});

it('allows superadmins to view the index', function () {
    $sa = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($sa)->get('/backstage/orga-teams')->assertSuccessful();
});

it('forbids users from updating an orga-team', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $team = OrgaTeam::factory()->create();

    $this->actingAs($user)
        ->patch("/backstage/orga-teams/{$team->id}", ['name' => 'Hacked'])
        ->assertForbidden();
});
