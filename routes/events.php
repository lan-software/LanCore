<?php

use App\Domain\Event\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'edit'])->name('events.edit');
    Route::patch('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::patch('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
    Route::patch('events/{event}/unpublish', [EventController::class, 'unpublish'])->name('events.unpublish');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
});
