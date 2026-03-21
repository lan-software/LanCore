<?php

use App\Domain\Games\Http\Controllers\GameController;
use App\Domain\Games\Http\Controllers\GameModeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('games', [GameController::class, 'index'])->name('games.index');
    Route::get('games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('games', [GameController::class, 'store'])->name('games.store');
    Route::get('games/{game}', [GameController::class, 'edit'])->name('games.edit');
    Route::patch('games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::delete('games/{game}', [GameController::class, 'destroy'])->name('games.destroy');

    Route::get('games/{game}/modes/create', [GameModeController::class, 'create'])->name('games.modes.create');
    Route::post('games/{game}/modes', [GameModeController::class, 'store'])->name('games.modes.store');
    Route::get('games/{game}/modes/{mode}', [GameModeController::class, 'edit'])->name('games.modes.edit');
    Route::patch('games/{game}/modes/{mode}', [GameModeController::class, 'update'])->name('games.modes.update');
    Route::delete('games/{game}/modes/{mode}', [GameModeController::class, 'destroy'])->name('games.modes.destroy');
});
