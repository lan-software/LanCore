<?php

use App\Domain\Webhook\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('backstage/webhooks')->group(function () {
    Route::get('/', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::get('create', [WebhookController::class, 'create'])->name('webhooks.create');
    Route::post('/', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::get('{webhook}', [WebhookController::class, 'show'])->name('webhooks.show');
    Route::get('{webhook}/edit', [WebhookController::class, 'edit'])->name('webhooks.edit');
    Route::patch('{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
});
