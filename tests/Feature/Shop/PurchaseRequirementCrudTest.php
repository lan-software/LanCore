<?php

use App\Domain\Shop\Models\PurchaseRequirement;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view purchase requirements index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    PurchaseRequirement::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/purchase-requirements')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('purchase-requirements/Index')
                ->has('requirements.data', 3)
        );
});

it('allows admins to view create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/purchase-requirements/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('purchase-requirements/Create')
                ->has('ticketTypes')
                ->has('addons')
        );
});

it('allows admins to store a purchase requirement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->post('/purchase-requirements', [
            'name' => 'Age Verification',
            'description' => 'Must verify age',
            'requirements_content' => '<p>You must be 18+</p>',
            'acknowledgements' => ['I confirm I am 18 or older'],
            'is_active' => true,
            'ticket_type_ids' => [$ticketType->id],
            'addon_ids' => [$addon->id],
        ])
        ->assertRedirect('/purchase-requirements');

    $requirement = PurchaseRequirement::where('name', 'Age Verification')->first();
    expect($requirement)->not->toBeNull()
        ->and($requirement->acknowledgements)->toBe(['I confirm I am 18 or older'])
        ->and($requirement->ticketTypes)->toHaveCount(1)
        ->and($requirement->addons)->toHaveCount(1);
});

it('allows admins to update a purchase requirement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $requirement = PurchaseRequirement::factory()->create();

    $this->actingAs($admin)
        ->patch("/purchase-requirements/{$requirement->id}", [
            'name' => 'Updated Requirement',
            'description' => $requirement->description,
            'acknowledgements' => ['Updated acknowledgement'],
            'is_active' => false,
            'ticket_type_ids' => [],
            'addon_ids' => [],
        ])
        ->assertRedirect();

    $updated = $requirement->fresh();
    expect($updated->name)->toBe('Updated Requirement')
        ->and($updated->is_active)->toBeFalse();
});

it('allows admins to delete a purchase requirement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $requirement = PurchaseRequirement::factory()->create();

    $this->actingAs($admin)
        ->delete("/purchase-requirements/{$requirement->id}")
        ->assertRedirect('/purchase-requirements');

    expect(PurchaseRequirement::find($requirement->id))->toBeNull();
});

it('denies regular users access to purchase requirements', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/purchase-requirements')
        ->assertForbidden();
});
