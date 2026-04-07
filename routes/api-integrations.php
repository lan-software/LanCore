<?php

use App\Domain\Competition\Http\Controllers\LanBracketsWebhookController;
use App\Domain\Integration\Http\Controllers\IntegrationSsoController;
use App\Domain\Integration\Http\Controllers\IntegrationUserController;
use App\Domain\Ticketing\Http\Controllers\Api\EntranceController;
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

Route::prefix('api/entrance')
    ->middleware(['integration.auth'])
    ->group(function () {
        Route::post('validate', [EntranceController::class, 'validateTicket'])->name('api.entrance.validate');
        Route::post('checkin', [EntranceController::class, 'checkin'])->name('api.entrance.checkin');
        Route::post('verify-checkin', [EntranceController::class, 'verifyCheckin'])->name('api.entrance.verify-checkin');
        Route::post('confirm-payment', [EntranceController::class, 'confirmPayment'])->name('api.entrance.confirm-payment');
        Route::post('override', [EntranceController::class, 'override'])->name('api.entrance.override');
        Route::get('search', [EntranceController::class, 'search'])->name('api.entrance.search');
        Route::get('stats', [EntranceController::class, 'stats'])->name('api.entrance.stats');
        Route::get('events', [EntranceController::class, 'events'])->name('api.entrance.events');
    });

/*
|--------------------------------------------------------------------------
| Webhook Routes (HMAC authenticated, stateless)
|--------------------------------------------------------------------------
*/
Route::post('webhooks/lanbrackets', LanBracketsWebhookController::class)->name('webhooks.lanbrackets');
