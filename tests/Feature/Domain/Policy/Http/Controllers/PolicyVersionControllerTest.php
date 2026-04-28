<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyLocaleDraft;
use App\Domain\Policy\Models\PolicyVersion;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Storage::fake(StorageRole::privateDiskName());
});

it('redirects the legacy versions/create URL to the policy show page', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/policies/{$policy->id}/versions/create")
        ->assertRedirect("/admin/policies/{$policy->id}");
});

it('publishes an editorial version from drafts', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# v1']);

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'is_non_editorial_change' => false,
        ])
        ->assertRedirect("/admin/policies/{$policy->id}");

    expect($policy->versions()->count())->toBe(1);
    expect($policy->fresh()->required_acceptance_version_number)->toBeNull();
});

it('publishes a non-editorial version with required public_statement', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# major']);
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'de', 'content' => '# major']);

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'is_non_editorial_change' => true,
            'public_statement' => 'We rewrote the data sharing section.',
        ])
        ->assertRedirect();

    expect($policy->versions()->count())->toBe(2);
    expect($policy->fresh()->required_acceptance_version_number)->toBe(1);
});

it('rejects non-editorial publish without public_statement', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# x']);

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'is_non_editorial_change' => true,
        ])
        ->assertSessionHasErrors('public_statement');
});

it('forbids non-admin users from publishing', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# x']);

    $this->actingAs($user)
        ->post("/admin/policies/{$policy->id}/versions")
        ->assertForbidden();
});

it('shares the same version_number across every locale of one publish', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# en']);
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'de', 'content' => '# de']);

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'is_non_editorial_change' => false,
        ])
        ->assertRedirect();

    $versions = PolicyVersion::where('policy_id', $policy->id)->get();
    expect($versions)->toHaveCount(2);
    expect($versions->pluck('version_number')->unique()->values()->all())->toBe([1]);
});
