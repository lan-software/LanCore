<?php

use App\Http\Controllers\Users\UserAuditByController;
use App\Http\Controllers\Users\UserAuditOnController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::patch('users/roles', [UserController::class, 'bulkAssignRole'])->name('users.bulk_assign_role');
    Route::delete('users', [UserController::class, 'bulkDestroy'])->name('users.bulk_destroy');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('users/{user}/personal-data', [UserController::class, 'updatePersonalData'])->name('users.update_personal_data');
    Route::get('users/{user}/audit/on', UserAuditOnController::class)->name('users.audits.on');
    Route::get('users/{user}/audit/by', UserAuditByController::class)->name('users.audits.by');
});
