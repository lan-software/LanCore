<?php

use App\Domain\EmailLog\Http\Controllers\EmailMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('admin/emails', [EmailMessageController::class, 'index'])
        ->name('admin.emails.index');
    Route::get('admin/emails/{emailMessage}', [EmailMessageController::class, 'show'])
        ->name('admin.emails.show');
});
