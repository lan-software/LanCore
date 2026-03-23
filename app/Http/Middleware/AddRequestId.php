<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates a unique request ID for each incoming request and propagates it
 * as an X-Request-ID response header. The ID is taken from the incoming
 * X-Request-ID header when present (so upstream proxies/load-balancers can
 * inject their own correlation IDs), otherwise a UUID v4 is generated.
 *
 * The ID is stored on the request attributes bag so it is accessible from
 * anywhere during the lifecycle (logging processors, exception handlers, …).
 *
 * Octane-safe: stores state on the request object, not in a singleton.
 */
class AddRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?: Str::uuid()->toString();

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
