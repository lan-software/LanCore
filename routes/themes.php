<?php

use App\Domain\Theme\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::get('themes/create', [ThemeController::class, 'create'])->name('themes.create');
    Route::post('themes', [ThemeController::class, 'store'])->name('themes.store');
    Route::patch('themes/default', [ThemeController::class, 'setDefault'])->name('themes.set-default');
    Route::get('themes/{theme}', [ThemeController::class, 'edit'])->name('themes.edit');
    Route::patch('themes/{theme}', [ThemeController::class, 'update'])->name('themes.update');
    Route::delete('themes/{theme}', [ThemeController::class, 'destroy'])->name('themes.destroy');
});
