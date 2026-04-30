<?php

use App\Domain\DataLifecycle\Http\Controllers\AdminAnonymizationLogController;
use App\Domain\DataLifecycle\Http\Controllers\AdminDeletionRequestController;
use App\Domain\DataLifecycle\Http\Controllers\AdminRetentionPolicyController;
use App\Domain\DataLifecycle\Http\Controllers\AdminUserGdprExportController;
use App\Domain\DataLifecycle\Http\Controllers\UserDeletionController;
use Illuminate\Support\Facades\Route;

/*
 * @see docs/mil-std-498/SSS.md CAP-DL-001..006
 * @see docs/mil-std-498/SRS.md DL-F-001..018
 */

// User-facing self-service flow. Confirmation link is reachable without
// `verified` (the user may be in grace and still need to confirm).
Route::middleware(['auth'])
    ->prefix('account/delete')
    ->name('data-lifecycle.account.')
    ->group(function (): void {
        Route::get('/', [UserDeletionController::class, 'show'])->name('show');
        Route::post('/', [UserDeletionController::class, 'request'])->name('request');
        Route::get('/confirm/{token}', [UserDeletionController::class, 'confirm'])->name('confirm');
        Route::delete('/{request}', [UserDeletionController::class, 'cancel'])->name('cancel');
    });

// Cancel via signed email link (works even after browser logout).
Route::get('account/delete/{request}/cancel-link', [UserDeletionController::class, 'cancelViaLink'])
    ->middleware('signed')
    ->name('data-lifecycle.account.cancel-via-link');

// Admin queue + admin-induced flows.
Route::middleware(['auth', 'verified', 'require.username'])
    ->prefix('admin/data-lifecycle')
    ->name('admin.data-lifecycle.')
    ->group(function (): void {
        Route::get('deletion-requests', [AdminDeletionRequestController::class, 'index'])
            ->name('deletion-requests.index');
        Route::get('deletion-requests/{deletionRequest}', [AdminDeletionRequestController::class, 'show'])
            ->name('deletion-requests.show');
        Route::post('deletion-requests', [AdminDeletionRequestController::class, 'store'])
            ->name('deletion-requests.store');
        Route::post('deletion-requests/{deletionRequest}/anonymize-now', [AdminDeletionRequestController::class, 'anonymizeNow'])
            ->name('deletion-requests.anonymize-now');
        Route::post('deletion-requests/{deletionRequest}/cancel', [AdminDeletionRequestController::class, 'cancel'])
            ->name('deletion-requests.cancel');

        Route::post('users/{user}/force-delete', [AdminDeletionRequestController::class, 'forceDelete'])
            ->name('users.force-delete');

        Route::get('retention-policies', [AdminRetentionPolicyController::class, 'index'])
            ->name('retention-policies.index');
        Route::patch('retention-policies/{retentionPolicy}', [AdminRetentionPolicyController::class, 'update'])
            ->name('retention-policies.update');

        Route::get('anonymization-log', [AdminAnonymizationLogController::class, 'index'])
            ->name('anonymization-log.index');

        Route::post('users/{user}/gdpr-export', [AdminUserGdprExportController::class, 'store'])
            ->name('users.gdpr-export');
    });
