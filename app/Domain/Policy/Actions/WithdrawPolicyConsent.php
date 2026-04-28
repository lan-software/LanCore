<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Events\ConsentWithdrawn;
use App\Domain\Policy\Exceptions\NoActivePolicyAcceptanceException;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Withdraws an active acceptance for a (user, policy) pair under
 * GDPR Art. 7(3). Operates on the most recently accepted version of the
 * policy that the user still holds an active acceptance for. The pivot
 * row is preserved (audit trail) — only `withdrawn_*` columns are set.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-007
 * @see docs/mil-std-498/SRS.md POL-F-013, POL-F-014
 */
class WithdrawPolicyConsent
{
    public function execute(
        User $user,
        Policy $policy,
        ?string $reason = null,
        ?Request $request = null,
    ): PolicyAcceptance {
        $acceptance = PolicyAcceptance::query()
            ->where('user_id', $user->id)
            ->whereNull('withdrawn_at')
            ->whereHas('version', fn ($q) => $q->where('policy_id', $policy->id))
            ->latest('accepted_at')
            ->first();

        if ($acceptance === null) {
            throw new NoActivePolicyAcceptanceException($user, $policy);
        }

        $userAgent = $request?->userAgent();

        $acceptance->forceFill([
            'withdrawn_at' => now(),
            'withdrawn_reason' => $reason,
            'withdrawn_ip' => $request?->ip(),
            'withdrawn_user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
        ])->save();

        ConsentWithdrawn::dispatch($acceptance->fresh(['version.policy', 'user']));

        return $acceptance;
    }
}
