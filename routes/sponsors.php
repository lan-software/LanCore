<?php

use App\Domain\Sponsoring\Http\Controllers\SponsorAuditController;
use App\Domain\Sponsoring\Http\Controllers\SponsorController;
use App\Domain\Sponsoring\Http\Controllers\SponsorLevelAuditController;
use App\Domain\Sponsoring\Http\Controllers\SponsorLevelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('sponsors', [SponsorController::class, 'index'])->name('sponsors.index');
    Route::get('sponsors/create', [SponsorController::class, 'create'])->name('sponsors.create');
    Route::post('sponsors', [SponsorController::class, 'store'])->name('sponsors.store');
    Route::get('sponsors/{sponsor}', [SponsorController::class, 'edit'])->name('sponsors.edit');
    Route::get('sponsors/{sponsor}/audit', SponsorAuditController::class)->name('sponsors.audit');
    Route::patch('sponsors/{sponsor}', [SponsorController::class, 'update'])->name('sponsors.update');
    Route::delete('sponsors/{sponsor}', [SponsorController::class, 'destroy'])->name('sponsors.destroy');

    Route::get('sponsor-levels', [SponsorLevelController::class, 'index'])->name('sponsor-levels.index');
    Route::get('sponsor-levels/create', [SponsorLevelController::class, 'create'])->name('sponsor-levels.create');
    Route::post('sponsor-levels', [SponsorLevelController::class, 'store'])->name('sponsor-levels.store');
    Route::get('sponsor-levels/{sponsorLevel}', [SponsorLevelController::class, 'edit'])->name('sponsor-levels.edit');
    Route::get('sponsor-levels/{sponsorLevel}/audit', SponsorLevelAuditController::class)->name('sponsor-levels.audit');
    Route::patch('sponsor-levels/{sponsorLevel}', [SponsorLevelController::class, 'update'])->name('sponsor-levels.update');
    Route::delete('sponsor-levels/{sponsorLevel}', [SponsorLevelController::class, 'destroy'])->name('sponsor-levels.destroy');
});
