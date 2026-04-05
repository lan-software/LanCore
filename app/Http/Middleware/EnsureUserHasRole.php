<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see docs/mil-std-498/SRS.md SEC-008, USR-F-006
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts comma-separated role names, e.g. `role:admin,superadmin`.
     * Aborts with 403 if the authenticated user has none of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $user->loadMissing('roles');

        foreach ($roles as $roleName) {
            $role = RoleName::tryFrom($roleName);

            if ($role && $user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
