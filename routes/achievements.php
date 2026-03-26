<?php

use App\Domain\Achievements\Http\Controllers\AchievementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('achievements-admin', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('achievements-admin/create', [AchievementController::class, 'create'])->name('achievements.create');
    Route::post('achievements-admin', [AchievementController::class, 'store'])->name('achievements.store');
    Route::get('achievements-admin/{achievement}', [AchievementController::class, 'edit'])->name('achievements.edit');
    Route::patch('achievements-admin/{achievement}', [AchievementController::class, 'update'])->name('achievements.update');
    Route::delete('achievements-admin/{achievement}', [AchievementController::class, 'destroy'])->name('achievements.destroy');
});
