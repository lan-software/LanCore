<?php

use App\Domain\Policy\Models\Policy;
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

it('shows the create-version page with prior acceptor count', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    User::factory()->count(3)->create()->each(
        fn ($u) => $version->acceptances()->create([
            'user_id' => $u->id,
            'accepted_at' => now(),
            'locale' => 'en',
            'source' => 'registration',
        ])
    );

    $this->actingAs($admin)
        ->get("/admin/policies/{$policy->id}/versions/create")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/policies/versions/Create')
            ->where('priorAcceptorCount', 3)
        );
});

it('publishes an editorial version', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'content' => '# v1',
            'is_non_editorial_change' => false,
        ])
        ->assertRedirect("/admin/policies/{$policy->id}");

    expect($policy->versions()->count())->toBe(1)
        ->and($policy->fresh()->required_acceptance_version_id)->toBeNull();
});

it('publishes a non-editorial version with required public_statement', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'content' => '# major rewrite',
            'is_non_editorial_change' => true,
            'public_statement' => 'We rewrote the data sharing section.',
        ])
        ->assertRedirect();

    expect($policy->fresh()->required_acceptance_version_id)->not->toBeNull();
});

it('rejects non-editorial publish without public_statement', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/versions", [
            'content' => '# v',
            'is_non_editorial_change' => true,
        ])
        ->assertSessionHasErrors('public_statement');
});

it('forbids non-admin users from publishing', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($user)
        ->post("/admin/policies/{$policy->id}/versions", [
            'content' => '# v',
        ])
        ->assertForbidden();
});
