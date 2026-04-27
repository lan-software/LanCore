<?php

use App\Domain\Event\Http\Controllers\PublicEventController;
use App\Domain\Shop\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\CookiePreferenceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventContextController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Onboarding\UsernameController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::get('upcoming-events', [PublicEventController::class, 'index'])->name('events.public');
Route::get('past-events', [PublicEventController::class, 'past'])->name('events.public.past');
Route::get('events/{event}/public', [PublicEventController::class, 'show'])->name('events.public.show');

Route::get('impressum', [LegalController::class, 'impressum'])->name('legal.impressum');
Route::get('imprint', [LegalController::class, 'impressum'])->name('legal.imprint');
Route::get('datenschutz', [LegalController::class, 'privacy'])->name('legal.datenschutz');
Route::get('privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::post('cookie-preferences', [CookiePreferenceController::class, 'update'])
    ->middleware('auth')
    ->name('cookie-preferences.update');

Route::get('files/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.file');

Route::post('webhooks/paypal', PayPalWebhookController::class)->name('webhooks.paypal');

Route::get('u/{username}', [PublicProfileController::class, 'show'])
    ->where('username', '[A-Za-z0-9_-]+')
    ->name('public-profile.show');

Route::middleware(['auth'])->group(function () {
    Route::get('onboarding/username', [UsernameController::class, 'show'])->name('onboarding.username.show');
    Route::post('onboarding/username', [UsernameController::class, 'update'])->name('onboarding.username.update');
});

Route::middleware(['auth', 'verified', 'require.username'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('event-context', [EventContextController::class, 'store'])->name('event-context.store');
    Route::delete('event-context', [EventContextController::class, 'destroy'])->name('event-context.destroy');
    Route::post('my-event-context', [EventContextController::class, 'storeMy'])->name('my-event-context.store');
    Route::delete('my-event-context', [EventContextController::class, 'destroyMy'])->name('my-event-context.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';
require __DIR__.'/venues.php';
require __DIR__.'/events.php';
require __DIR__.'/programs.php';
require __DIR__.'/sponsors.php';
require __DIR__.'/orga-teams.php';
require __DIR__.'/ticketing.php';
require __DIR__.'/shop.php';
require __DIR__.'/games.php';
require __DIR__.'/competitions.php';
require __DIR__.'/seating.php';
require __DIR__.'/news.php';
require __DIR__.'/announcements.php';
require __DIR__.'/achievements.php';
require __DIR__.'/notifications.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/integrations.php';
require __DIR__.'/orchestration.php';
