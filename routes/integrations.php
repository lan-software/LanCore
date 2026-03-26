<?php

use App\Domain\Integration\Http\Controllers\IntegrationAppController;
use App\Domain\Integration\Http\Controllers\IntegrationTokenController;
use App\Domain\Integration\Http\Controllers\IntegrationUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Integration Admin Routes (web, auth required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('integrations', [IntegrationAppController::class, 'index'])->name('integrations.index');
    Route::get('integrations/create', [IntegrationAppController::class, 'create'])->name('integrations.create');
    Route::post('integrations', [IntegrationAppController::class, 'store'])->name('integrations.store');
    Route::get('integrations/{integration}', [IntegrationAppController::class, 'edit'])->name('integrations.edit');
    Route::patch('integrations/{integration}', [IntegrationAppController::class, 'update'])->name('integrations.update');
    Route::delete('integrations/{integration}', [IntegrationAppController::class, 'destroy'])->name('integrations.destroy');

    Route::post('integrations/{integration}/tokens', [IntegrationTokenController::class, 'store'])->name('integrations.tokens.store');
    Route::post('integrations/{integration}/tokens/{token}/rotate', [IntegrationTokenController::class, 'rotate'])->name('integrations.tokens.rotate');
    Route::delete('integrations/{integration}/tokens/{token}', [IntegrationTokenController::class, 'destroy'])->name('integrations.tokens.destroy');
});

/*
|--------------------------------------------------------------------------
| Integration API Routes (token authenticated, stateless)
|--------------------------------------------------------------------------
*/
Route::prefix('api/integration')
    ->middleware(['integration.auth'])
    ->group(function () {
        Route::get('user/me', [IntegrationUserController::class, 'me'])->name('api.integration.user.me');
        Route::post('user/resolve', [IntegrationUserController::class, 'resolve'])->name('api.integration.user.resolve');
    });
