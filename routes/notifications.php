<?php

use App\Domain\Notification\Http\Controllers\NotificationController;
use App\Domain\Notification\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/archive', [NotificationController::class, 'archivedIndex'])->name('notifications.archive');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('notifications/{id}/archive', [NotificationController::class, 'archive'])->name('notifications.archive-item');
    Route::patch('notifications/archive-all', [NotificationController::class, 'archiveAll'])->name('notifications.archive-all');

    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});
