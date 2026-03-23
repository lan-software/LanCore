<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the Prometheus /metrics endpoint with a bearer token.
 *
 * The token is configured via the METRICS_TOKEN environment variable
 * (mapped through config/prometheus.php). When no token is configured the
 * endpoint returns 503 so it is safe-off by default.
 *
 * Uses hash_equals() for a constant-time comparison to prevent timing attacks.
 */
class EnsureValidMetricsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('prometheus.token');

        if (empty($expected)) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Metrics endpoint is not configured. Set METRICS_TOKEN in your environment.');
        }

        $provided = $request->bearerToken();

        if (empty($provided) || ! hash_equals($expected, $provided)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid or missing metrics token.');
        }

        return $next($request);
    }
}
