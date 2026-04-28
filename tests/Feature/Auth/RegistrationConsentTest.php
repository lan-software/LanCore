<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;

it('refuses registration when a required policy is not accepted', function (): void {
    $policy = Policy::factory()->requiredForRegistration()->create();
    PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'published_at' => now()->subDay(),
    ]);

    $this->post('/register', [
        'name' => 'Acceptance Tester',
        'username' => 'acceptance_tester',
        'email' => 'consent@example.com',
        'password' => 'Sup3rStr0ng-Pass!',
        'password_confirmation' => 'Sup3rStr0ng-Pass!',
        'accepted_policy_version_ids' => [],
    ])->assertSessionHasErrors('accepted_policy_version_ids');

    expect(User::where('email', 'consent@example.com')->exists())->toBeFalse();
});

it('records acceptances on successful registration', function (): void {
    $policy = Policy::factory()->requiredForRegistration()->create();
    $version = PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->post('/register', [
        'name' => 'Acceptance Tester',
        'username' => 'acceptance_tester',
        'email' => 'consent@example.com',
        'password' => 'Sup3rStr0ng-Pass!',
        'password_confirmation' => 'Sup3rStr0ng-Pass!',
        'accepted_policy_version_ids' => [$version->id],
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'consent@example.com')->firstOrFail();
    expect(
        $user->policyAcceptances()
            ->where('policy_version_id', $version->id)
            ->where('source', 'registration')
            ->whereNull('withdrawn_at')
            ->exists()
    )->toBeTrue();
});

it('allows registration when no required policies exist', function (): void {
    $this->post('/register', [
        'name' => 'No Policies User',
        'username' => 'nopolicies',
        'email' => 'no-policies@example.com',
        'password' => 'Sup3rStr0ng-Pass!',
        'password_confirmation' => 'Sup3rStr0ng-Pass!',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'no-policies@example.com')->exists())->toBeTrue();
});
