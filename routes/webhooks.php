<?php

use App\Domain\Webhook\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('webhooks-admin', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::get('webhooks-admin/create', [WebhookController::class, 'create'])->name('webhooks.create');
    Route::post('webhooks-admin', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::get('webhooks-admin/{webhook}', [WebhookController::class, 'edit'])->name('webhooks.edit');
    Route::patch('webhooks-admin/{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('webhooks-admin/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
});
