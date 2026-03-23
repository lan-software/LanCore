<?php

use App\Http\Middleware\AddRequestId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

// ---------------------------------------------------------------------------
// /metrics endpoint — security
// ---------------------------------------------------------------------------

describe('metrics endpoint security', function () {
    beforeEach(function () {
        config(['prometheus.token' => 'test-secret-token-abc123']);
        config(['prometheus.cache' => 'array']);
    });

    it('returns 503 when METRICS_TOKEN is not configured', function () {
        config(['prometheus.token' => null]);

        $this->get('/metrics')->assertStatus(503);
    });

    it('returns 403 when no bearer token is provided', function () {
        $this->get('/metrics')->assertStatus(403);
    });

    it('returns 403 when an incorrect bearer token is provided', function () {
        $this->withToken('wrong-token')
            ->get('/metrics')
            ->assertStatus(403);
    });

    it('returns 200 with the correct bearer token', function () {
        $this->withToken('test-secret-token-abc123')
            ->get('/metrics')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=UTF-8');
    });

    it('returns 403 when the request IP is not in the allow-list', function () {
        config(['prometheus.allowed_ips' => ['1.2.3.4']]);

        $this->withToken('test-secret-token-abc123')
            ->get('/metrics')
            ->assertStatus(403);
    });
});

// ---------------------------------------------------------------------------
// AddRequestId middleware
// ---------------------------------------------------------------------------

describe('AddRequestId middleware', function () {
    it('generates a UUID and adds it to the X-Request-ID response header', function () {
        $response = $this->get('/up');

        $requestId = $response->headers->get('X-Request-ID');

        expect($requestId)->not->toBeEmpty();
        expect(Str::isUuid($requestId))->toBeTrue();
    });

    it('echoes back a caller-provided X-Request-ID header unchanged', function () {
        $providedId = Str::uuid()->toString();

        $response = $this->withHeader('X-Request-ID', $providedId)->get('/up');

        expect($response->headers->get('X-Request-ID'))->toBe($providedId);
    });

    it('stores the request ID on the request attributes bag', function () {
        $captured = null;

        app('router')->get('/_test_request_id', function (Request $request) use (&$captured) {
            $captured = $request->attributes->get('request_id');

            return response('ok');
        })->middleware(AddRequestId::class);

        $this->get('/_test_request_id');

        expect($captured)->not->toBeNull();
        expect(Str::isUuid($captured))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// TrackHttpMetrics middleware
// ---------------------------------------------------------------------------

describe('TrackHttpMetrics middleware', function () {
    beforeEach(function () {
        Redis::del('metrics:http:requests', 'metrics:http:duration_ms');

        // Register a lightweight web route so TrackHttpMetrics runs.
        app('router')->get('/_test_metrics_route', fn () => response('ok', 200))->middleware('web');
    });

    it('increments the Redis request counter after a web request', function () {
        $this->get('/_test_metrics_route')->assertOk();

        $count = Redis::hGet('metrics:http:requests', 'GET_200');

        expect((int) $count)->toBeGreaterThanOrEqual(1);
    });

    it('records an EMA duration entry after a web request', function () {
        $this->get('/_test_metrics_route')->assertOk();

        $keys = Redis::hGetAll('metrics:http:duration_ms');

        expect($keys)->not->toBeEmpty();
    });
});
