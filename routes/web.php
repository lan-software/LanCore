<?php

use App\Domain\Event\Http\Controllers\PublicEventController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::get('upcoming-events', PublicEventController::class)->name('events.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';
require __DIR__.'/venues.php';
require __DIR__.'/events.php';
require __DIR__.'/programs.php';
