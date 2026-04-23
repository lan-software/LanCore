<?php

use App\Domain\Seating\Http\Controllers\SeatPickerController;
use App\Domain\Ticketing\Http\Controllers\AddonAuditController;
use App\Domain\Ticketing\Http\Controllers\AddonController;
use App\Domain\Ticketing\Http\Controllers\AdminTicketController;
use App\Domain\Ticketing\Http\Controllers\TicketCategoryAuditController;
use App\Domain\Ticketing\Http\Controllers\TicketCategoryController;
use App\Domain\Ticketing\Http\Controllers\TicketController;
use App\Domain\Ticketing\Http\Controllers\TicketTypeAuditController;
use App\Domain\Ticketing\Http\Controllers\TicketTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // My Tickets
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('tickets/{ticket}/download', [TicketController::class, 'download'])->name('tickets.download');
    Route::get('tickets/{ticket}/qr', [TicketController::class, 'qrCode'])->name('tickets.qr');
    Route::patch('tickets/{ticket}/manager', [TicketController::class, 'updateManager'])->name('tickets.update-manager');
    Route::post('tickets/{ticket}/users', [TicketController::class, 'addUser'])->name('tickets.add-user');
    Route::delete('tickets/{ticket}/users/{user}', [TicketController::class, 'removeUser'])->name('tickets.remove-user');
    Route::post('tickets/{ticket}/rotate-token', [TicketController::class, 'rotateTokenUser'])
        ->middleware('throttle:10,1')
        ->name('tickets.rotate-token');

    // Seat picker (end-user) — pick/change seats for ticket users
    Route::get('events/{event}/seats', [SeatPickerController::class, 'show'])->name('events.seats.picker');
    Route::post('events/{event}/seats', [SeatPickerController::class, 'store'])->name('events.seats.assign');
    Route::delete('events/{event}/seats/{assignment}', [SeatPickerController::class, 'destroy'])->name('events.seats.release');

    // Admin: Ticket Types
    Route::get('ticket-types', [TicketTypeController::class, 'index'])->name('ticket-types.index');
    Route::get('ticket-types/create', [TicketTypeController::class, 'create'])->name('ticket-types.create');
    Route::post('ticket-types', [TicketTypeController::class, 'store'])->name('ticket-types.store');
    Route::get('ticket-types/{ticketType}', [TicketTypeController::class, 'edit'])->name('ticket-types.edit');
    Route::get('ticket-types/{ticketType}/audit', TicketTypeAuditController::class)->name('ticket-types.audit');
    Route::patch('ticket-types/{ticketType}', [TicketTypeController::class, 'update'])->name('ticket-types.update');
    Route::delete('ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])->name('ticket-types.destroy');

    // Admin: Ticket Categories
    Route::get('ticket-categories', [TicketCategoryController::class, 'index'])->name('ticket-categories.index');
    Route::get('ticket-categories/create', [TicketCategoryController::class, 'create'])->name('ticket-categories.create');
    Route::post('ticket-categories', [TicketCategoryController::class, 'store'])->name('ticket-categories.store');
    Route::get('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'edit'])->name('ticket-categories.edit');
    Route::get('ticket-categories/{ticketCategory}/audit', TicketCategoryAuditController::class)->name('ticket-categories.audit');
    Route::patch('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update'])->name('ticket-categories.update');
    Route::delete('ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy'])->name('ticket-categories.destroy');

    // Admin: Addons
    Route::get('ticket-addons', [AddonController::class, 'index'])->name('ticket-addons.index');
    Route::get('ticket-addons/create', [AddonController::class, 'create'])->name('ticket-addons.create');
    Route::post('ticket-addons', [AddonController::class, 'store'])->name('ticket-addons.store');
    Route::get('ticket-addons/{ticketAddon}', [AddonController::class, 'edit'])->name('ticket-addons.edit');
    Route::get('ticket-addons/{ticketAddon}/audit', AddonAuditController::class)->name('ticket-addons.audit');
    Route::patch('ticket-addons/{ticketAddon}', [AddonController::class, 'update'])->name('ticket-addons.update');
    Route::delete('ticket-addons/{ticketAddon}', [AddonController::class, 'destroy'])->name('ticket-addons.destroy');

    // Admin: Tickets Management
    Route::get('admin-tickets', [AdminTicketController::class, 'index'])->name('admin-tickets.index');
    Route::get('admin-tickets/{ticket}', [AdminTicketController::class, 'show'])->name('admin-tickets.show');
    Route::post('admin-tickets/{ticket}/rotate-token', [AdminTicketController::class, 'rotateToken'])->name('admin-tickets.rotate-token');
});
