<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to create a policy type', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/admin/policies/types', [
            'key' => 'code_of_conduct',
            'label' => 'Code of Conduct',
            'description' => 'CoC umbrella type',
        ])
        ->assertRedirect();

    expect(PolicyType::where('key', 'code_of_conduct')->exists())->toBeTrue();
});

it('rejects invalid policy type keys', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/admin/policies/types', [
            'key' => 'Invalid Key With Spaces',
            'label' => 'Bad',
        ])
        ->assertSessionHasErrors('key');
});

it('forbids non-admin users from creating a policy type', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/admin/policies/types', [
            'key' => 'tos',
            'label' => 'TOS',
        ])
        ->assertForbidden();
});

it('allows admins to update a policy type', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $type = PolicyType::factory()->create(['label' => 'Old Label']);

    $this->actingAs($admin)
        ->put("/admin/policies/types/{$type->id}", [
            'label' => 'New Label',
        ])
        ->assertRedirect();

    expect($type->fresh()->label)->toBe('New Label');
});

it('allows admins to delete a policy type that is not in use', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $type = PolicyType::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/policies/types/{$type->id}")
        ->assertRedirect();

    expect(PolicyType::find($type->id))->toBeNull();
});

it('refuses to delete a policy type that is in use', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $type = PolicyType::factory()->create();
    Policy::factory()->create(['policy_type_id' => $type->id]);

    $this->actingAs($admin)
        ->delete("/admin/policies/types/{$type->id}")
        ->assertSessionHasErrors('policyType');

    expect(PolicyType::find($type->id))->not->toBeNull();
});
