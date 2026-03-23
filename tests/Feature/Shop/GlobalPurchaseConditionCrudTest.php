<?php

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view global purchase conditions index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    GlobalPurchaseCondition::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/global-purchase-conditions')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('global-purchase-conditions/Index')
                ->has('conditions.data', 2)
        );
});

it('allows admins to store a global purchase condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/global-purchase-conditions', [
            'name' => 'Terms of Service',
            'description' => 'General T&C',
            'content' => '<p>Accept our terms</p>',
            'acknowledgement_label' => 'I accept the Terms of Service',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertRedirect('/global-purchase-conditions');

    $condition = GlobalPurchaseCondition::where('name', 'Terms of Service')->first();
    expect($condition)->not->toBeNull()
        ->and($condition->is_required)->toBeTrue()
        ->and($condition->acknowledgement_label)->toBe('I accept the Terms of Service');
});

it('allows admins to update a global purchase condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $condition = GlobalPurchaseCondition::factory()->create();

    $this->actingAs($admin)
        ->patch("/global-purchase-conditions/{$condition->id}", [
            'name' => 'Updated Condition',
            'description' => $condition->description,
            'content' => $condition->content,
            'acknowledgement_label' => 'Updated label',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->assertRedirect();

    $updated = $condition->fresh();
    expect($updated->name)->toBe('Updated Condition')
        ->and($updated->is_required)->toBeFalse()
        ->and($updated->sort_order)->toBe(5);
});

it('allows admins to delete a global purchase condition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $condition = GlobalPurchaseCondition::factory()->create();

    $this->actingAs($admin)
        ->delete("/global-purchase-conditions/{$condition->id}")
        ->assertRedirect('/global-purchase-conditions');

    expect(GlobalPurchaseCondition::find($condition->id))->toBeNull();
});

it('denies regular users access to global purchase conditions', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/global-purchase-conditions')
        ->assertForbidden();
});
