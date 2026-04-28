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

it('allows admins to view the policies index', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Policy::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/admin/policies')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/policies/Index')
            ->has('policies', 3)
            ->has('policyTypes')
        );
});

it('forbids non-admin users from the policies index', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/admin/policies')
        ->assertForbidden();
});

it('redirects guests to login from the policies index', function (): void {
    $this->get('/admin/policies')->assertRedirect('/login');
});

it('allows admins to create a policy', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $type = PolicyType::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/policies', [
            'policy_type_id' => $type->id,
            'key' => 'tos',
            'name' => 'Terms of Service',
            'description' => 'Top-level TOS',
            'is_required_for_registration' => true,
            'sort_order' => 10,
        ])
        ->assertRedirect();

    $policy = Policy::where('key', 'tos')->first();
    expect($policy)->not->toBeNull()
        ->and($policy->name)->toBe('Terms of Service')
        ->and($policy->is_required_for_registration)->toBeTrue()
        ->and($policy->policy_type_id)->toBe($type->id);
});

it('rejects duplicate policy keys', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $type = PolicyType::factory()->create();
    Policy::factory()->create(['key' => 'tos', 'policy_type_id' => $type->id]);

    $this->actingAs($admin)
        ->post('/admin/policies', [
            'policy_type_id' => $type->id,
            'key' => 'tos',
            'name' => 'Duplicate',
        ])
        ->assertSessionHasErrors('key');
});

it('allows admins to update a policy', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create(['name' => 'Original']);

    $this->actingAs($admin)
        ->put("/admin/policies/{$policy->id}", [
            'name' => 'Updated Name',
            'sort_order' => 99,
        ])
        ->assertRedirect();

    $fresh = $policy->fresh();
    expect($fresh->name)->toBe('Updated Name')
        ->and($fresh->sort_order)->toBe(99);
});

it('allows admins to archive a policy', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/archive")
        ->assertRedirect('/admin/policies');

    expect($policy->fresh()->archived_at)->not->toBeNull();
});

it('shows a policy with its versions', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/policies/{$policy->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/policies/Show')
            ->where('policy.id', $policy->id)
        );
});
