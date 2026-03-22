<?php

use App\Domain\Seating\Models\SeatPlan;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $seatPlan = SeatPlan::factory()->create();

    $this->actingAs($admin)
        ->get("/seat-plans/{$seatPlan->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('seating/Audit')
                ->has('seatPlan')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the seat plan audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $seatPlan = SeatPlan::factory()->create();

    $this->actingAs($user)
        ->get("/seat-plans/{$seatPlan->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for seat plan audit', function () {
    $seatPlan = SeatPlan::factory()->create();

    $this->get("/seat-plans/{$seatPlan->id}/audit")
        ->assertRedirect('/login');
});
