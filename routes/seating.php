<?php

use App\Domain\Seating\Http\Controllers\SeatPlanAuditController;
use App\Domain\Seating\Http\Controllers\SeatPlanBackgroundController;
use App\Domain\Seating\Http\Controllers\SeatPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('seat-plans', [SeatPlanController::class, 'index'])->name('seat-plans.index');
    Route::get('seat-plans/create', [SeatPlanController::class, 'create'])->name('seat-plans.create');
    Route::post('seat-plans', [SeatPlanController::class, 'store'])->name('seat-plans.store');
    Route::get('seat-plans/{seatPlan}', [SeatPlanController::class, 'edit'])->name('seat-plans.edit');
    Route::get('seat-plans/{seatPlan}/audit', SeatPlanAuditController::class)->name('seat-plans.audit');
    Route::patch('seat-plans/{seatPlan}', [SeatPlanController::class, 'update'])->name('seat-plans.update');
    Route::delete('seat-plans/{seatPlan}', [SeatPlanController::class, 'destroy'])->name('seat-plans.destroy');

    Route::post('seat-plans/{seatPlan}/background', [SeatPlanBackgroundController::class, 'storePlan'])
        ->name('seat-plans.background.store');
    Route::delete('seat-plans/{seatPlan}/background', [SeatPlanBackgroundController::class, 'destroyPlan'])
        ->name('seat-plans.background.destroy');
    Route::post('seat-plans/{seatPlan}/blocks/{block}/background', [SeatPlanBackgroundController::class, 'storeBlock'])
        ->name('seat-plans.blocks.background.store');
    Route::delete('seat-plans/{seatPlan}/blocks/{block}/background', [SeatPlanBackgroundController::class, 'destroyBlock'])
        ->name('seat-plans.blocks.background.destroy');
});
