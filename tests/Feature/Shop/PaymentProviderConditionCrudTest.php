<?php

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\PaymentProviderCondition;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view payment provider conditions index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    PaymentProviderCondition::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/payment-provider-conditions')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('payment-provider-conditions/Index')
                ->has('conditions.data', 2)
        );
});

it('allows admins to store a payment provider condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/payment-provider-conditions', [
            'payment_method' => PaymentMethod::Stripe->value,
            'name' => 'Stripe Processing Fee Notice',
            'description' => 'Notice about fees',
            'content' => '<p>A processing fee may apply</p>',
            'acknowledgement_label' => 'I understand a processing fee may apply',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertRedirect('/payment-provider-conditions');

    $condition = PaymentProviderCondition::where('name', 'Stripe Processing Fee Notice')->first();
    expect($condition)->not->toBeNull()
        ->and($condition->payment_method)->toBe(PaymentMethod::Stripe)
        ->and($condition->is_required)->toBeTrue();
});

it('allows admins to update a payment provider condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $condition = PaymentProviderCondition::factory()->forStripe()->create();

    $this->actingAs($admin)
        ->patch("/payment-provider-conditions/{$condition->id}", [
            'payment_method' => PaymentMethod::OnSite->value,
            'name' => 'Updated Condition',
            'description' => $condition->description,
            'content' => $condition->content,
            'acknowledgement_label' => 'Updated label',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 3,
        ])
        ->assertRedirect();

    $updated = $condition->fresh();
    expect($updated->name)->toBe('Updated Condition')
        ->and($updated->payment_method)->toBe(PaymentMethod::OnSite)
        ->and($updated->is_required)->toBeFalse();
});

it('allows admins to delete a payment provider condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $condition = PaymentProviderCondition::factory()->create();

    $this->actingAs($admin)
        ->delete("/payment-provider-conditions/{$condition->id}")
        ->assertRedirect('/payment-provider-conditions');

    expect(PaymentProviderCondition::find($condition->id))->toBeNull();
});

it('denies regular users access to payment provider conditions', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/payment-provider-conditions')
        ->assertForbidden();
});

it('validates payment method enum on store', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/payment-provider-conditions', [
            'payment_method' => 'invalid_method',
            'name' => 'Test',
            'acknowledgement_label' => 'Test label',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors(['payment_method']);
});
