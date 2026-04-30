<?php

namespace App\Domain\DataLifecycle\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the authenticated user has an active deletion request (in either
 * pending_email_confirm or pending_grace state), the account is locked to
 * read-only mode. Mutating requests are blocked except for routes whose name
 * appears in the allow-list below.
 *
 * @see docs/mil-std-498/SRS.md DL-F-006
 */
class EnforceAccountReadOnlyDuringGrace
{
    /**
     * Route names that remain available to a user whose account is in grace.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'data-lifecycle.account.cancel',
        'data-lifecycle.account.cancel-via-link',
        'data-lifecycle.account.confirm',
        'data-lifecycle.account.show',
        'gdpr.export.self-download',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isPendingDeletion() || $request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();
        if ($routeName !== null && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Your account is in deletion grace period and is read-only. Cancel the deletion to make changes.',
            ], 423);
        }

        return Inertia::render('account/DeletionPending', [
            'message' => 'Your account is in deletion grace period and is read-only.',
        ])->toResponse($request)->setStatusCode(423);
    }
}
