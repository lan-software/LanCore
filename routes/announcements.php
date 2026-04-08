<?php

use App\Domain\Announcement\Http\Controllers\AnnouncementController;
use App\Domain\Announcement\Http\Controllers\AnnouncementDismissalController;
use App\Domain\Announcement\Http\Controllers\PublicAnnouncementController;
use App\Domain\Announcement\Http\Controllers\PublicAnnouncementFeedController;
use Illuminate\Support\Facades\Route;

// Public JSON feed consumed by satellite apps.
Route::get('/api/announcements/feed', PublicAnnouncementFeedController::class)
    ->middleware('throttle:60,1')
    ->name('announcements.feed');

// Public announcement archive page (authenticated)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('events/{event}/announcements', PublicAnnouncementController::class)->name('announcements.public');
    Route::post('announcements/{announcement}/dismiss', [AnnouncementDismissalController::class, 'store'])->name('announcements.dismiss');
});

// Admin routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('announcements-admin', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('announcements-admin/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('announcements-admin', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('announcements-admin/{announcement}', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::patch('announcements-admin/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('announcements-admin/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::delete('announcements-admin/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
