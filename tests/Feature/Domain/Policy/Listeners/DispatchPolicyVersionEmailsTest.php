<?php

use App\Domain\Policy\Events\PolicyVersionPublished;
use App\Domain\Policy\Jobs\SendPolicyVersionPublishedEmailJob;
use App\Domain\Policy\Listeners\DispatchPolicyVersionEmails;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

it('queues one job per distinct prior acceptor on a non-editorial publish', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $oldVersion = PolicyVersion::factory()->for($policy)->create(['version_number' => 1]);
    $newVersion = PolicyVersion::factory()->for($policy)->create(['version_number' => 2, 'is_non_editorial_change' => true]);

    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        PolicyAcceptance::factory()->create([
            'user_id' => $user->id,
            'policy_version_id' => $oldVersion->id,
        ]);
    }

    PolicyAcceptance::factory()->create([
        'user_id' => $users->first()->id,
        'policy_version_id' => $oldVersion->id,
    ]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyVersionPublished($newVersion, isNonEditorial: true, silent: false),
    );

    Bus::assertDispatchedTimes(SendPolicyVersionPublishedEmailJob::class, 3);
});

it('does not queue any jobs for an editorial publish (silent=true)', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $oldVersion = PolicyVersion::factory()->for($policy)->create();
    $newVersion = PolicyVersion::factory()->for($policy)->create();

    PolicyAcceptance::factory()->count(2)->create([
        'policy_version_id' => $oldVersion->id,
    ]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyVersionPublished($newVersion, isNonEditorial: false, silent: true),
    );

    Bus::assertNotDispatched(SendPolicyVersionPublishedEmailJob::class);
});

it('skips users who only ever accepted the new version', function (): void {
    Bus::fake();

    $policy = Policy::factory()->create();
    $newVersion = PolicyVersion::factory()->for($policy)->create(['is_non_editorial_change' => true]);

    PolicyAcceptance::factory()->create(['policy_version_id' => $newVersion->id]);

    app(DispatchPolicyVersionEmails::class)->handle(
        new PolicyVersionPublished($newVersion, isNonEditorial: true, silent: false),
    );

    Bus::assertNotDispatched(SendPolicyVersionPublishedEmailJob::class);
});
