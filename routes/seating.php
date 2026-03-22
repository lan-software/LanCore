<?php

use App\Domain\Seating\Http\Controllers\SeatPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('seat-plans', [SeatPlanController::class, 'index'])->name('seat-plans.index');
    Route::get('seat-plans/create', [SeatPlanController::class, 'create'])->name('seat-plans.create');
    Route::post('seat-plans', [SeatPlanController::class, 'store'])->name('seat-plans.store');
    Route::get('seat-plans/{seatPlan}', [SeatPlanController::class, 'edit'])->name('seat-plans.edit');
    Route::patch('seat-plans/{seatPlan}', [SeatPlanController::class, 'update'])->name('seat-plans.update');
    Route::delete('seat-plans/{seatPlan}', [SeatPlanController::class, 'destroy'])->name('seat-plans.destroy');
});
