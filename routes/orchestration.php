<?php

use App\Domain\Orchestration\Http\Controllers\ExternalApiController;
use App\Domain\Orchestration\Http\Controllers\GameServerController;
use App\Domain\Orchestration\Http\Controllers\OrchestrationJobController;
use App\Domain\Orchestration\Http\Controllers\Tmt2WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('game-servers', [GameServerController::class, 'index'])->name('game-servers.index');
    Route::get('game-servers/create', [GameServerController::class, 'create'])->name('game-servers.create');
    Route::post('game-servers', [GameServerController::class, 'store'])->name('game-servers.store');
    Route::get('game-servers/{gameServer}', [GameServerController::class, 'edit'])->name('game-servers.edit');
    Route::patch('game-servers/{gameServer}', [GameServerController::class, 'update'])->name('game-servers.update');
    Route::delete('game-servers/{gameServer}', [GameServerController::class, 'destroy'])->name('game-servers.destroy');
    Route::post('game-servers/{gameServer}/force-release', [GameServerController::class, 'forceRelease'])->name('game-servers.force-release');

    Route::get('orchestration-jobs', [OrchestrationJobController::class, 'index'])->name('orchestration-jobs.index');
    Route::get('orchestration-jobs/{orchestrationJob}', [OrchestrationJobController::class, 'show'])->name('orchestration-jobs.show');
    Route::post('orchestration-jobs/{orchestrationJob}/retry', [OrchestrationJobController::class, 'retry'])->name('orchestration-jobs.retry');
    Route::post('orchestration-jobs/{orchestrationJob}/cancel', [OrchestrationJobController::class, 'cancel'])->name('orchestration-jobs.cancel');

    Route::get('external-apis', [ExternalApiController::class, 'index'])->name('external-apis.index');
    Route::post('external-apis/test-tmt2', [ExternalApiController::class, 'testTmt2'])->name('external-apis.test-tmt2');
    Route::post('external-apis/test-stripe', [ExternalApiController::class, 'testStripe'])->name('external-apis.test-stripe');
});

// TMT2 webhook — no auth middleware, secured by URL secret
Route::post('webhooks/tmt2/{orchestrationJob}', Tmt2WebhookController::class)->name('webhooks.tmt2');
