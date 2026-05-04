<?php

use App\Domain\Achievements\Http\Controllers\AchievementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('backstage/achievements')->group(function () {
    Route::get('/', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('create', [AchievementController::class, 'create'])->name('achievements.create');
    Route::post('/', [AchievementController::class, 'store'])->name('achievements.store');
    Route::get('{achievement}', [AchievementController::class, 'edit'])->name('achievements.edit');
    Route::patch('{achievement}', [AchievementController::class, 'update'])->name('achievements.update');
    Route::delete('{achievement}', [AchievementController::class, 'destroy'])->name('achievements.destroy');
});
