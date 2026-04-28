<?php

use App\Domain\Policy\Http\Controllers\ConsentWithdrawalController;
use App\Domain\Policy\Http\Controllers\PolicyController;
use App\Domain\Policy\Http\Controllers\PolicyTypeController;
use App\Domain\Policy\Http\Controllers\PolicyVersionController;
use App\Domain\Policy\Http\Controllers\PublicPolicyController;
use App\Domain\Policy\Http\Controllers\RequiredPoliciesController;
use Illuminate\Support\Facades\Route;

/*
 * Public read-only policy view. Bound on `key` for stable URLs across
 * version bumps. Allowlisted by RequirePolicyAcceptance.
 */
Route::get('/policies/{policy:key}', [PublicPolicyController::class, 'show'])
    ->name('policies.show');

/*
 * Re-acceptance gate. Reachable to authenticated users without other
 * gating — the gate route IS the gap-resolver, so RequirePolicyAcceptance
 * allowlists it.
 */
Route::middleware(['auth'])->group(function (): void {
    Route::get('/policies/required', [RequiredPoliciesController::class, 'show'])
        ->name('policies.required.show');
    Route::post('/policies/required/accept', [RequiredPoliciesController::class, 'accept'])
        ->name('policies.required.accept');

    Route::post('/settings/consent/{policy}/withdraw', [ConsentWithdrawalController::class, 'store'])
        ->name('settings.consent.withdraw');
});

/*
 * Admin routes for the Policy feature domain.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-001..002
 * @see docs/mil-std-498/SRS.md POL-F-001..006
 */
Route::middleware(['auth', 'verified', 'require.username'])
    ->prefix('admin/policies')
    ->name('admin.policies.')
    ->group(function (): void {
        Route::get('/', [PolicyController::class, 'index'])->name('index');
        Route::get('/create', [PolicyController::class, 'create'])->name('create');
        Route::post('/', [PolicyController::class, 'store'])->name('store');
        Route::get('/{policy}', [PolicyController::class, 'show'])->name('show');
        Route::get('/{policy}/edit', [PolicyController::class, 'edit'])->name('edit');
        Route::put('/{policy}', [PolicyController::class, 'update'])->name('update');
        Route::post('/{policy}/archive', [PolicyController::class, 'archive'])->name('archive');

        Route::get('/{policy}/versions/create', [PolicyVersionController::class, 'create'])->name('versions.create');
        Route::post('/{policy}/versions', [PolicyVersionController::class, 'store'])->name('versions.store');

        Route::post('/types', [PolicyTypeController::class, 'store'])->name('types.store');
        Route::put('/types/{policyType}', [PolicyTypeController::class, 'update'])->name('types.update');
        Route::delete('/types/{policyType}', [PolicyTypeController::class, 'destroy'])->name('types.destroy');
    });
