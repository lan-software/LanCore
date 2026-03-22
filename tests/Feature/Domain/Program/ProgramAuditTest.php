<?php

use App\Domain\Program\Models\Program;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->get("/programs/{$program->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Audit')
                ->has('program')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the program audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $program = Program::factory()->create();

    $this->actingAs($user)
        ->get("/programs/{$program->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for program audit', function () {
    $program = Program::factory()->create();

    $this->get("/programs/{$program->id}/audit")
        ->assertRedirect('/login');
});
