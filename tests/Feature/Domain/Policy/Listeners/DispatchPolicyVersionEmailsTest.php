<?php

use App\Domain\Policy\Events\PolicyPublished;
use App\Domain\Policy\Jobs\SendPolicyPublishedEmailJob;
use App\Domain\Policy\Listeners\DispatchPolicyVersionEmails;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

it('queues one job per distinct prior acceptor on a non-editorial publish', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    PolicyVersion::factory()->for($policy)->create(['version_number' => 1, 'locale' => 'en']);
    $oldEn = PolicyVersion::where('policy_id', $policy->id)->first();
    PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'locale' => 'en', 'is_non_editorial_change' => true]);
    PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'locale' => 'de', 'is_non_editorial_change' => true]);

    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        PolicyAcceptance::factory()->create([
            'user_id' => $user->id,
            'policy_version_id' => $oldEn->id,
        ]);
    }

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyPublished($policy, versionNumber: 2, isNonEditorial: true, silent: false),
    );

    Bus::assertDispatchedTimes(SendPolicyPublishedEmailJob::class, 3);
});

it('does not queue any jobs for an editorial publish (silent=true)', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $old = PolicyVersion::factory()->for($policy)->create(['version_number' => 1]);
    PolicyAcceptance::factory()->count(2)->create(['policy_version_id' => $old->id]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyPublished($policy, versionNumber: 2, isNonEditorial: false, silent: true),
    );

    Bus::assertNotDispatched(SendPolicyPublishedEmailJob::class);
});

it('skips users who only ever accepted the new version itself', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $newVersion = PolicyVersion::factory()->for($policy)->create(['version_number' => 1, 'is_non_editorial_change' => true]);
    PolicyAcceptance::factory()->create(['policy_version_id' => $newVersion->id]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyPublished($policy, versionNumber: 1, isNonEditorial: true, silent: false),
    );

    Bus::assertNotDispatched(SendPolicyPublishedEmailJob::class);
});

it('emits one job per user even when the publish has multiple locales', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $oldEn = PolicyVersion::factory()->for($policy)->create(['version_number' => 1, 'locale' => 'en']);
    $oldDe = PolicyVersion::factory()->for($policy)->create(['version_number' => 1, 'locale' => 'de']);
    PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'locale' => 'en', 'is_non_editorial_change' => true]);
    PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'locale' => 'de', 'is_non_editorial_change' => true]);

    $alice = User::factory()->create();
    PolicyAcceptance::factory()->create(['user_id' => $alice->id, 'policy_version_id' => $oldEn->id]);
    PolicyAcceptance::factory()->create(['user_id' => $alice->id, 'policy_version_id' => $oldDe->id]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyPublished($policy, versionNumber: 2, isNonEditorial: true, silent: false),
    );

    Bus::assertDispatchedTimes(SendPolicyPublishedEmailJob::class, 1);
});
