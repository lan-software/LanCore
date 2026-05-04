<?php

use App\Domain\Newsletter\Http\Controllers\Admin\NewsletterListController;
use App\Domain\Newsletter\Http\Controllers\Admin\NewsletterListSyncController;
use App\Domain\Newsletter\Http\Controllers\Admin\UserSubscriptionsController;
use App\Domain\Newsletter\Http\Controllers\Public\NewsletterSubscribeController;
use App\Domain\Newsletter\Http\Controllers\User\EmailSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('newsletter-lists', [NewsletterListController::class, 'index'])->name('newsletter-lists.index');
    Route::get('newsletter-lists/create', [NewsletterListController::class, 'create'])->name('newsletter-lists.create');
    Route::post('newsletter-lists', [NewsletterListController::class, 'store'])->name('newsletter-lists.store');
    Route::get('newsletter-lists/{newsletterList}', [NewsletterListController::class, 'edit'])->name('newsletter-lists.edit');
    Route::patch('newsletter-lists/{newsletterList}', [NewsletterListController::class, 'update'])->name('newsletter-lists.update');
    Route::delete('newsletter-lists/{newsletterList}', [NewsletterListController::class, 'destroy'])->name('newsletter-lists.destroy');

    Route::post('newsletter-lists/sync/fetch', [NewsletterListSyncController::class, 'fetch'])->name('newsletter-lists.sync.fetch');
    Route::post('newsletter-lists/{newsletterList}/opt-in-all', [NewsletterListSyncController::class, 'optInAllUsers'])->name('newsletter-lists.opt-in-all');

    Route::patch('users/{user}/newsletter-subscriptions', [UserSubscriptionsController::class, 'update'])->name('users.newsletter-subscriptions.update');

    Route::get('settings/email', [EmailSettingsController::class, 'edit'])->name('email-settings.edit');
    Route::patch('settings/email', [EmailSettingsController::class, 'update'])->name('email-settings.update');
});

Route::post('newsletter/subscribe', [NewsletterSubscribeController::class, 'store'])
    ->middleware('throttle:newsletter-signup')
    ->name('newsletter.subscribe.public');
