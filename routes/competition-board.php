<?php

use App\Domain\CompetitionSchedule\Http\Controllers\CompetitionBoardController;
use App\Domain\CompetitionSchedule\Http\Controllers\NextMatchProposalController;
use App\Domain\CompetitionSchedule\Http\Controllers\RoundScheduleController;
use App\Domain\CompetitionSchedule\Http\Controllers\StageScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Competition Board — Gantt scheduling + next-match proposals
|--------------------------------------------------------------------------
|
| @see docs/mil-std-498/SRS.md COMP-SCH-001..007
| @see docs/mil-std-498/SRS.md COMP-RND-001..007
*/

/*
 * Admin Competition Board. The board is scoped via the AppSidebar Event
 * Selector (selected_event_id in session), per competition-board.md
 * "Page route: /backstage/competition-board".
 */
Route::middleware(['auth', 'verified'])->prefix('backstage')->group(function () {
    Route::get('competition-board', [CompetitionBoardController::class, 'showCurrent'])
        ->name('competition-board.show');
    Route::get('competition-board/data', [CompetitionBoardController::class, 'dataCurrent'])
        ->name('competition-board.data');
    Route::post('competition-board/sync', [CompetitionBoardController::class, 'syncCurrent'])
        ->name('competition-board.sync');
    Route::post('competition-board/auto-fit', [CompetitionBoardController::class, 'autoFitCurrent'])
        ->name('competition-board.auto-fit');

    Route::patch('stage-schedules/{schedule}', [StageScheduleController::class, 'update'])
        ->name('stage-schedules.update');

    Route::patch('round-schedules/{schedule}', [RoundScheduleController::class, 'update'])
        ->name('round-schedules.update');
});

/*
 * Player-facing "Play Next" recommendation. Per spec, this is primarily
 * a user-facing surface for signed-in competition participants
 * (competition-board.md → Player-facing UX).
 */
Route::middleware(['auth', 'verified'])->prefix('portal')->group(function () {
    Route::get('play-next', [NextMatchProposalController::class, 'forCurrentUser'])
        ->name('portal.play-next.index');
});
