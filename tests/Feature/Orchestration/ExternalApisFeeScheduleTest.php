<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('exposes Stripe + PayPal fee schedules on the External APIs page', function () {
    config([
        'shop.provider_fees.stripe' => [
            'percentage' => 150,
            'fixed_cents' => 25,
            'currency' => 'eur',
            'note' => 'Stripe note',
        ],
        'shop.provider_fees.paypal' => [
            'percentage' => 249,
            'fixed_cents' => 35,
            'currency' => 'eur',
            'note' => 'PayPal note',
        ],
    ]);

    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/backstage/external-apis')
        ->assertInertia(
            fn ($page) => $page
                ->component('orchestration/apis/Index')
                ->where('connections.stripe.fee_schedule.percentage', 1.5)
                ->where('connections.stripe.fee_schedule.fixed_cents', 25)
                ->where('connections.paypal.fee_schedule.percentage', 2.49)
                ->where('connections.paypal.fee_schedule.fixed_cents', 35),
        );
});
