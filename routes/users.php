<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::patch('users/roles', [UserController::class, 'bulkAssignRole'])->name('users.bulk_assign_role');
    Route::delete('users', [UserController::class, 'bulkDestroy'])->name('users.bulk_destroy');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
});
