<?php

namespace App\Http\Middleware;

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects authenticated users to the re-acceptance gate when a non-editorial
 * publish has bumped `policies.required_acceptance_version_number` to a version
 * the user has not actively accepted in any locale.
 *
 * Editorial publishes leave `required_acceptance_version_number` untouched, so
 * they never trigger this middleware.
 *
 * @see app/Http/Middleware/RequireUsername.php template this mirrors.
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 * @see docs/mil-std-498/SRS.md POL-F-011
 */
class RequirePolicyAcceptance
{
    /**
     * @var list<string>
     */
    private const ALLOWLIST_PATTERNS = [
        'policies/required',
        'policies/required/*',
        'policies/*',
        'legal',
        'legal/*',
        'settings/consent/*',
        'login',
        'register',
        'logout',
        'language/*',
        'two-factor*',
        'user/confirm-password',
        'forgot-password',
        'reset-password/*',
        'email/verify',
        'email/verify/*',
        'sanctum/*',
        'horizon/*',
        'telescope/*',
        'pulse',
        'pulse/*',
        '_debugbar/*',
        'livewire/*',
        'onboarding/username',
        'onboarding/username/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($request->is(...self::ALLOWLIST_PATTERNS)) {
            return $next($request);
        }

        if (! $this->hasGap($user)) {
            return $next($request);
        }

        if (! $request->expectsJson() && $request->isMethod('GET')) {
            $request->session()?->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('policies.required.show');
    }

    private function hasGap(User $user): bool
    {
        $required = Policy::query()
            ->active()
            ->whereNotNull('required_acceptance_version_number')
            ->get(['id', 'required_acceptance_version_number']);

        if ($required->isEmpty()) {
            return false;
        }

        foreach ($required as $policy) {
            $satisfied = PolicyAcceptance::query()
                ->where('user_id', $user->id)
                ->whereNull('withdrawn_at')
                ->whereHas(
                    'version',
                    fn ($q) => $q
                        ->where('policy_id', $policy->id)
                        ->where('version_number', $policy->required_acceptance_version_number),
                )
                ->exists();

            if (! $satisfied) {
                return true;
            }
        }

        return false;
    }
}
