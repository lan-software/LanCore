<?php

use App\Domain\Venue\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('venues/create', [VenueController::class, 'create'])->name('venues.create');
    Route::post('venues', [VenueController::class, 'store'])->name('venues.store');
    Route::get('venues/{venue}', [VenueController::class, 'edit'])->name('venues.edit');
    Route::patch('venues/{venue}', [VenueController::class, 'update'])->name('venues.update');
    Route::delete('venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');
});
