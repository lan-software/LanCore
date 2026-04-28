<?php

use App\Domain\Policy\Actions\WithdrawPolicyConsent;
use App\Domain\Policy\Events\ConsentWithdrawn;
use App\Domain\Policy\Exceptions\NoActivePolicyAcceptanceException;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

it('marks the latest active acceptance as withdrawn and dispatches ConsentWithdrawn', function (): void {
    Event::fake([ConsentWithdrawn::class]);

    $user = User::factory()->create();
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();

    $acceptance = PolicyAcceptance::factory()->create([
        'user_id' => $user->id,
        'policy_version_id' => $version->id,
    ]);

    $request = Request::create('/foo', 'POST', server: [
        'REMOTE_ADDR' => '198.51.100.5',
        'HTTP_USER_AGENT' => 'WithdrawalAgent/1.0',
    ]);

    $result = app(WithdrawPolicyConsent::class)->execute(
        $user,
        $policy,
        'Switching providers',
        $request,
    );

    expect($result->id)->toBe($acceptance->id)
        ->and($result->withdrawn_at)->not->toBeNull()
        ->and($result->withdrawn_reason)->toBe('Switching providers')
        ->and($result->withdrawn_ip)->toBe('198.51.100.5')
        ->and($result->withdrawn_user_agent)->toBe('WithdrawalAgent/1.0');

    Event::assertDispatched(ConsentWithdrawn::class);
});

it('throws when the user has no active acceptance for the policy', function (): void {
    $user = User::factory()->create();
    $policy = Policy::factory()->create();

    expect(fn () => app(WithdrawPolicyConsent::class)->execute($user, $policy))
        ->toThrow(NoActivePolicyAcceptanceException::class);
});
