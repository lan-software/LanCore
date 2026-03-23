<?php

use App\Domain\Shop\Models\CheckoutAcknowledgement;
use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Shop\Models\PaymentProviderCondition;
use App\Domain\Shop\Models\PurchaseRequirement;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('saves a global purchase condition acknowledgement', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $condition = GlobalPurchaseCondition::factory()->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'global_purchase_condition',
            'acknowledgeable_id' => $condition->id,
        ])
        ->assertOk()
        ->assertJsonStructure(['acknowledged_at']);

    $this->assertDatabaseHas('checkout_acknowledgements', [
        'user_id' => $user->id,
        'acknowledgeable_type' => GlobalPurchaseCondition::class,
        'acknowledgeable_id' => $condition->id,
    ]);
});

it('saves a payment provider condition acknowledgement', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $condition = PaymentProviderCondition::factory()->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'payment_provider_condition',
            'acknowledgeable_id' => $condition->id,
        ])
        ->assertOk()
        ->assertJsonStructure(['acknowledged_at']);

    $this->assertDatabaseHas('checkout_acknowledgements', [
        'user_id' => $user->id,
        'acknowledgeable_type' => PaymentProviderCondition::class,
        'acknowledgeable_id' => $condition->id,
    ]);
});

it('saves a purchase requirement acknowledgement with key', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $requirement = PurchaseRequirement::factory()->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'purchase_requirement',
            'acknowledgeable_id' => $requirement->id,
            'acknowledgement_key' => '0',
        ])
        ->assertOk()
        ->assertJsonStructure(['acknowledged_at']);

    $this->assertDatabaseHas('checkout_acknowledgements', [
        'user_id' => $user->id,
        'acknowledgeable_type' => PurchaseRequirement::class,
        'acknowledgeable_id' => $requirement->id,
        'acknowledgement_key' => '0',
    ]);
});

it('updates existing acknowledgement instead of duplicating', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $condition = GlobalPurchaseCondition::factory()->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'global_purchase_condition',
            'acknowledgeable_id' => $condition->id,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'global_purchase_condition',
            'acknowledgeable_id' => $condition->id,
        ])
        ->assertOk();

    expect(CheckoutAcknowledgement::where('user_id', $user->id)
        ->where('acknowledgeable_type', GlobalPurchaseCondition::class)
        ->where('acknowledgeable_id', $condition->id)
        ->count()
    )->toBe(1);
});

it('rejects invalid acknowledgeable type', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'invalid_type',
            'acknowledgeable_id' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('acknowledgeable_type');
});

it('returns 404 for non-existent acknowledgeable', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->postJson('/cart/acknowledge', [
            'acknowledgeable_type' => 'global_purchase_condition',
            'acknowledgeable_id' => 9999,
        ])
        ->assertNotFound();
});

it('requires authentication', function () {
    $condition = GlobalPurchaseCondition::factory()->create();

    $this->postJson('/cart/acknowledge', [
        'acknowledgeable_type' => 'global_purchase_condition',
        'acknowledgeable_id' => $condition->id,
    ])->assertUnauthorized();
});
