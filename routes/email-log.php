<?php

use App\Domain\EmailLog\Http\Controllers\EmailMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('backstage/emails', [EmailMessageController::class, 'index'])
        ->name('admin.emails.index');
    Route::get('backstage/emails/{emailMessage}', [EmailMessageController::class, 'show'])
        ->name('admin.emails.show');
});
