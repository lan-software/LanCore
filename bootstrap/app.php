<?php

use App\Domain\Integration\Http\Middleware\AuthenticateIntegration;
use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\EnforceDemoGuardrails;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RecordDemoActivity;
use App\Http\Middleware\RequireUsername;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackHttpMetrics;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware([])->group(base_path('routes/api-integrations.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->preventRequestForgery(except: ['stripe/*', 'webhooks/*']);

        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Prepend globally so every request (web + API + health) gets an ID
        // and metrics are tracked before the response is sent.
        $middleware->prepend(AddRequestId::class);

        $middleware->web(append: [
            TrackHttpMetrics::class,
            HandleAppearance::class,
            SetLocale::class,
            EnforceDemoGuardrails::class,
            RecordDemoActivity::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'integration.auth' => AuthenticateIntegration::class,
            'require.username' => RequireUsername::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
