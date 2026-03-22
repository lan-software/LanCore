<?php

use App\Domain\Program\Http\Controllers\ProgramAuditController;
use App\Domain\Program\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
    Route::get('programs/create', [ProgramController::class, 'create'])->name('programs.create');
    Route::post('programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('programs/{program}', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::get('programs/{program}/audit', ProgramAuditController::class)->name('programs.audit');
    Route::patch('programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::delete('programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
});
