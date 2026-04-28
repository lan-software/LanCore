<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;

it('shows only the policies the user has not actively accepted', function (): void {
    $accepted = Policy::factory()->create();
    $acceptedVersion = PolicyVersion::factory()->for($accepted)->create();
    $accepted->update(['required_acceptance_version_number' => $acceptedVersion->version_number]);

    $missing = Policy::factory()->create();
    $missingVersion = PolicyVersion::factory()->for($missing)->create();
    $missing->update(['required_acceptance_version_number' => $missingVersion->version_number]);

    $user = User::factory()->create();
    $user->policyAcceptances()->create([
        'policy_version_id' => $acceptedVersion->id,
        'accepted_at' => now(),
        'locale' => 'en',
        'source' => 'registration',
    ]);

    $this->actingAs($user)
        ->get('/policies/required')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('policies/Required')
            ->has('policies', 1)
            ->where('policies.0.id', $missing->id)
        );
});

it('records acceptances for every submitted version and clears the gate', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/policies/required/accept', [
            'policy_version_ids' => [$version->id],
        ])
        ->assertRedirect();

    expect(
        $user->policyAcceptances()
            ->where('policy_version_id', $version->id)
            ->where('source', 're_acceptance_gate')
            ->whereNull('withdrawn_at')
            ->exists()
    )->toBeTrue();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

it('rejects empty acceptance submissions', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/policies/required/accept', [
            'policy_version_ids' => [],
        ])
        ->assertSessionHasErrors('policy_version_ids');
});

it('rejects non-existent version IDs', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/policies/required/accept', [
            'policy_version_ids' => [99999],
        ])
        ->assertSessionHasErrors('policy_version_ids.0');
});

it('does not collide with the public /policies/{key} route', function (): void {
    Policy::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/policies/required')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('policies/Required'));
});
