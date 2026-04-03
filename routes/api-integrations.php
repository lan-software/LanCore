<?php

use App\Domain\Competition\Http\Controllers\LanBracketsWebhookController;
use App\Domain\Integration\Http\Controllers\IntegrationSsoController;
use App\Domain\Integration\Http\Controllers\IntegrationUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Integration API Routes (token authenticated, stateless)
|--------------------------------------------------------------------------
|
| These routes are loaded outside the web middleware group so they do not
| carry CSRF, session, or cookie middleware. Authentication is handled
| entirely via the `integration.auth` (Bearer token) middleware.
|
*/
Route::prefix('api/integration')
    ->middleware(['integration.auth'])
    ->group(function () {
        Route::get('user/me', [IntegrationUserController::class, 'me'])->name('api.integration.user.me');
        Route::post('user/resolve', [IntegrationUserController::class, 'resolve'])->name('api.integration.user.resolve');
        Route::post('sso/exchange', [IntegrationSsoController::class, 'exchange'])->name('api.integration.sso.exchange');
    });

/*
|--------------------------------------------------------------------------
| Webhook Routes (HMAC authenticated, stateless)
|--------------------------------------------------------------------------
*/
Route::post('webhooks/lanbrackets', LanBracketsWebhookController::class)->name('webhooks.lanbrackets');
