<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects authenticated users without a username to the one-time
 * onboarding step. Allowlists routes that must remain accessible during
 * onboarding (logout, language switch, the onboarding routes themselves,
 * static assets).
 *
 * Users registered after USR-F-022 ships always supply a username at
 * signup, so they never trigger this middleware.
 *
 * @see docs/mil-std-498/SRS.md USR-F-022
 * @see docs/mil-std-498/SDD.md §5.1 Middleware Pipeline (RequireUsername)
 */
class RequireUsername
{
    /**
     * @var list<string>
     */
    private const ALLOWLIST_PATTERNS = [
        'onboarding/username',
        'onboarding/username/*',
        'logout',
        'language/*',
        'two-factor*',
        'user/confirm-password',
        'sanctum/*',
        'horizon/*',
        'telescope/*',
        'pulse',
        'pulse/*',
        '_debugbar/*',
        'livewire/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->username !== null) {
            return $next($request);
        }

        if ($request->is(...self::ALLOWLIST_PATTERNS)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
            // Stash the intended URL only when it makes sense to come back to.
        } else {
            $request->session()?->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('onboarding.username.show');
    }
}
