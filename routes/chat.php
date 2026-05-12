<?php

use App\Domain\Chat\Http\Controllers\ChatMentionSearchController;
use App\Domain\Chat\Http\Controllers\ChatMessageController;
use App\Domain\Chat\Http\Controllers\ChatModerationController;
use App\Domain\Chat\Http\Controllers\ChatRoomController;
use Illuminate\Support\Facades\Route;

/*
| CHT routes — all are gated by `auth` + `verified`. Authorization for view /
| post / moderate is delegated to the room's bound RoomPolicy in the
| controllers, so route middleware stays simple here.
*/
Route::middleware(['auth', 'verified'])->prefix('chat')->name('chat.')->group(function () {
    Route::get('rooms/{room}', [ChatRoomController::class, 'show'])->name('rooms.show');
    Route::post('rooms/{room}/observer', [ChatRoomController::class, 'joinObserver'])->name('rooms.observer.join');
    Route::delete('rooms/{room}/observer', [ChatRoomController::class, 'leaveObserver'])->name('rooms.observer.leave');

    Route::get('rooms/{room}/messages', [ChatMessageController::class, 'index'])->name('rooms.messages.index');
    Route::post('rooms/{room}/messages', [ChatMessageController::class, 'store'])->name('rooms.messages.store');
    Route::delete('rooms/{room}/messages/{message}', [ChatMessageController::class, 'destroy'])->name('rooms.messages.destroy');

    Route::get('rooms/{room}/mention-search', ChatMentionSearchController::class)->name('rooms.mention-search');

    Route::post('rooms/{room}/moderation/mute/{user}', [ChatModerationController::class, 'mute'])->name('rooms.moderation.mute');
    Route::post('rooms/{room}/moderation/unmute/{user}', [ChatModerationController::class, 'unmute'])->name('rooms.moderation.unmute');
    Route::post('rooms/{room}/moderation/close', [ChatModerationController::class, 'close'])->name('rooms.moderation.close');
    Route::post('rooms/{room}/moderation/reopen', [ChatModerationController::class, 'reopen'])->name('rooms.moderation.reopen');
});
