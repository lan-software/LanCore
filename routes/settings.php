<?php

use App\Domain\Notification\Http\Controllers\NotificationSettingsController;
use App\Domain\Notification\Http\Controllers\ProgramSubscriptionController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\Settings\PrivacyController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ProfileMediaController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SidebarFavoriteController;
use App\Http\Controllers\Settings\TicketDiscoveryController;
use App\Http\Controllers\Settings\UserAchievementsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('settings/profile/avatar', [ProfileMediaController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('settings/profile/avatar', [ProfileMediaController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('settings/profile/banner', [ProfileMediaController::class, 'uploadBanner'])->name('profile.banner.upload');
    Route::delete('settings/profile/banner', [ProfileMediaController::class, 'destroyBanner'])->name('profile.banner.destroy');

    Route::get('settings/profile/preview', [PublicProfileController::class, 'preview'])->name('profile.preview');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

    Route::post('programs/{program}/subscribe', [ProgramSubscriptionController::class, 'toggle'])->name('programs.subscribe.toggle');

    Route::get('settings/ticket-discovery', [TicketDiscoveryController::class, 'edit'])->name('ticket-discovery.edit');
    Route::patch('settings/ticket-discovery', [TicketDiscoveryController::class, 'update'])->name('ticket-discovery.update');

    Route::get('settings/privacy', [PrivacyController::class, 'edit'])->name('privacy.edit');
    Route::patch('settings/privacy', [PrivacyController::class, 'update'])->name('privacy.update');

    Route::post('settings/sidebar-favorites/toggle', [SidebarFavoriteController::class, 'toggle'])->name('sidebar-favorites.toggle');

    Route::get('settings/achievements', UserAchievementsController::class)->name('user-achievements.index');

    // Admin: Organization settings
    Route::get('organization-settings', [OrganizationSettingsController::class, 'index'])->name('organization-settings.index');
    Route::patch('organization-settings', [OrganizationSettingsController::class, 'update'])->name('organization-settings.update');
    Route::post('organization-settings/logo', [OrganizationSettingsController::class, 'uploadLogo'])->name('organization-settings.upload-logo');
    Route::delete('organization-settings/logo', [OrganizationSettingsController::class, 'removeLogo'])->name('organization-settings.remove-logo');
});
