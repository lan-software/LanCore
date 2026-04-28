<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;

it('lets through users with no required policies', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

it('redirects users with a missing acceptance to the gate', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('policies.required.show'));
});

it('lets through users who have actively accepted the required version', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();
    PolicyAcceptance::factory()->create([
        'user_id' => $user->id,
        'policy_version_id' => $version->id,
        'withdrawn_at' => null,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

it('redirects users whose acceptance was withdrawn', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();
    PolicyAcceptance::factory()->withdrawn('changed mind')->create([
        'user_id' => $user->id,
        'policy_version_id' => $version->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('policies.required.show'));
});

it('does not redirect requests for allowlisted paths', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/policies/required')
        ->assertSuccessful();
});

it('stashes the intended URL on a GET redirect', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard?utm=test')
        ->assertRedirect(route('policies.required.show'));

    expect(session()->get('url.intended'))->toContain('/dashboard');
});

it('archived policies do not contribute to the gap', function (): void {
    $policy = Policy::factory()->archived()->create();
    $version = PolicyVersion::factory()->for($policy)->create();
    $policy->update(['required_acceptance_version_number' => $version->version_number]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

it('editorial publish does not create a gap (pointer unchanged)', function (): void {
    $policy = Policy::factory()->create();
    $v1 = PolicyVersion::factory()->for($policy)->create(['version_number' => 1]);
    $policy->update(['required_acceptance_version_number' => $v1->version_number]);
    $user = User::factory()->create();
    PolicyAcceptance::factory()->create([
        'user_id' => $user->id,
        'policy_version_id' => $v1->id,
    ]);

    PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'is_non_editorial_change' => false]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});
