<?php

use App\Domain\Shop\Models\Voucher;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a voucher', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $voucher = Voucher::factory()->create();

    $this->actingAs($admin)
        ->get("/vouchers/{$voucher->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('vouchers/Audit')
                ->has('voucher')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the voucher audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $voucher = Voucher::factory()->create();

    $this->actingAs($user)
        ->get("/vouchers/{$voucher->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for voucher audit', function () {
    $voucher = Voucher::factory()->create();

    $this->get("/vouchers/{$voucher->id}/audit")
        ->assertRedirect('/login');
});
