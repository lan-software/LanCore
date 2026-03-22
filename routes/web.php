<?php

use App\Domain\Event\Http\Controllers\PublicEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::get('upcoming-events', PublicEventController::class)->name('events.public');

Route::get('files/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.file');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';
require __DIR__.'/venues.php';
require __DIR__.'/events.php';
require __DIR__.'/programs.php';
require __DIR__.'/sponsors.php';
require __DIR__.'/ticketing.php';
require __DIR__.'/shop.php';
require __DIR__.'/games.php';
require __DIR__.'/seating.php';
