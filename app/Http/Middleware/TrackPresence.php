<?php

namespace App\Http\Middleware;

use App\Domain\Presence\Services\PresenceTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Heartbeat the current user's presence on every authenticated web request.
 *
 * @see docs/mil-std-498/SRS.md PRS-F-004
 */
class TrackPresence
{
    public function __construct(private readonly PresenceTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $this->tracker->touch($user);
        }

        return $next($request);
    }
}
