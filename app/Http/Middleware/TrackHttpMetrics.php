<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records HTTP request counts and an exponential-moving-average response time
 * into Redis after every request. These counters are consumed by the
 * PrometheusServiceProvider at scrape time and exposed to Prometheus.
 *
 * Counters are stored in two Redis hashes:
 *   metrics:http:requests   — field "{METHOD}_{STATUS_CODE}", value int
 *   metrics:http:duration_ms — field "{METHOD}_{ROUTE_NAME}", value float (EMA)
 *
 * Octane-safe: no shared state; all writes go directly to Redis.
 */
class TrackHttpMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->record($request, $response, $startTime);

        return $response;
    }

    private function record(Request $request, Response $response, float $startTime): void
    {
        $method = $request->method();
        $statusCode = (string) $response->getStatusCode();
        $durationMs = (microtime(true) - $startTime) * 1000;

        Redis::hIncrBy('metrics:http:requests', "{$method}_{$statusCode}", 1);

        $routeName = $this->resolveRouteName($request);
        $durationKey = "{$method}_{$routeName}";
        $current = (float) (Redis::hGet('metrics:http:duration_ms', $durationKey) ?: 0);
        $ema = $current === 0.0 ? $durationMs : 0.9 * $current + 0.1 * $durationMs;
        Redis::hSet('metrics:http:duration_ms', $durationKey, round($ema, 2));
    }

    private function resolveRouteName(Request $request): string
    {
        $route = $request->route();

        if ($route instanceof Route) {
            $name = $route->getName();

            if ($name) {
                return str_replace('.', '_', $name);
            }

            $action = $route->getActionName();
            if ($action && $action !== 'Closure') {
                return strtolower(str_replace(['\\', '@', '/'], '_', class_basename($action)));
            }
        }

        return 'unknown';
    }
}
