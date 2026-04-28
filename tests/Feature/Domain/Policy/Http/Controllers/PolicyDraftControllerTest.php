<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyLocaleDraft;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('adds a new locale draft', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/drafts", ['locale' => 'en'])
        ->assertRedirect();

    expect($policy->drafts()->where('locale', 'en')->exists())->toBeTrue();
});

it('rejects duplicate locales', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en']);

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/drafts", ['locale' => 'en'])
        ->assertSessionHasErrors('locale');
});

it('rejects unknown locales', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/policies/{$policy->id}/drafts", ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('updates draft content (and allows empty content)', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => 'old']);

    $this->actingAs($admin)
        ->put("/admin/policies/{$policy->id}/drafts/en", ['content' => 'new'])
        ->assertRedirect();

    expect($policy->drafts()->where('locale', 'en')->first()->content)->toBe('new');

    $this->actingAs($admin)
        ->put("/admin/policies/{$policy->id}/drafts/en", ['content' => ''])
        ->assertRedirect();

    expect($policy->drafts()->where('locale', 'en')->first()->content)->toBe('');
});

it('records the editing admin', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '']);

    $this->actingAs($admin)
        ->put("/admin/policies/{$policy->id}/drafts/en", ['content' => 'mine']);

    expect($policy->drafts()->first()->updated_by_user_id)->toBe($admin->id);
});

it('removes a draft when the policy has more than one locale', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en']);
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'de']);

    $this->actingAs($admin)
        ->delete("/admin/policies/{$policy->id}/drafts/de")
        ->assertRedirect();

    expect($policy->drafts()->pluck('locale')->all())->toBe(['en']);
});

it('refuses to remove the last remaining draft', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en']);

    $this->actingAs($admin)
        ->delete("/admin/policies/{$policy->id}/drafts/en")
        ->assertSessionHasErrors('locale');

    expect($policy->drafts()->count())->toBe(1);
});

it('forbids non-admins from editing drafts', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $policy = Policy::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en']);

    $this->actingAs($user)
        ->put("/admin/policies/{$policy->id}/drafts/en", ['content' => 'nope'])
        ->assertForbidden();
});
