<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Events\PolicyAccepted;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Upserts a (user, policy_version) acceptance row. Re-acceptance after a
 * prior withdrawal clears the withdrawal columns and refreshes accepted_at.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-004
 * @see docs/mil-std-498/SRS.md POL-F-010, POL-F-012
 */
class RecordPolicyAcceptance
{
    public function execute(
        User $user,
        PolicyVersion $version,
        PolicyAcceptanceSource $source,
        ?Request $request = null,
    ): PolicyAcceptance {
        $userAgent = $request?->userAgent();

        $acceptance = PolicyAcceptance::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'policy_version_id' => $version->id,
            ],
            [
                'accepted_at' => now(),
                'locale' => $version->locale,
                'ip_address' => $request?->ip(),
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
                'source' => $source->value,
                'withdrawn_at' => null,
                'withdrawn_reason' => null,
                'withdrawn_ip' => null,
                'withdrawn_user_agent' => null,
            ],
        );

        PolicyAccepted::dispatch($acceptance);

        return $acceptance;
    }
}
