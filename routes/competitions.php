<?php

use App\Domain\Competition\Http\Controllers\CompetitionController;
use App\Domain\Competition\Http\Controllers\MatchResultController;
use App\Domain\Competition\Http\Controllers\TeamController;
use App\Domain\Competition\Http\Controllers\UserCompetitionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('competitions', [CompetitionController::class, 'index'])->name('competitions.index');
    Route::get('competitions/create', [CompetitionController::class, 'create'])->name('competitions.create');
    Route::post('competitions', [CompetitionController::class, 'store'])->name('competitions.store');
    Route::get('competitions/{competition}/edit', [CompetitionController::class, 'edit'])->name('competitions.edit');
    Route::patch('competitions/{competition}', [CompetitionController::class, 'update'])->name('competitions.update');
    Route::delete('competitions/{competition}', [CompetitionController::class, 'destroy'])->name('competitions.destroy');

    Route::post('competitions/{competition}/teams', [TeamController::class, 'store'])->name('competitions.teams.store');
    Route::post('competitions/{competition}/teams/{team}/join', [TeamController::class, 'join'])->name('competitions.teams.join');
    Route::post('competitions/{competition}/teams/{team}/leave', [TeamController::class, 'leave'])->name('competitions.teams.leave');
    Route::delete('competitions/{competition}/teams/{team}', [TeamController::class, 'destroy'])->name('competitions.teams.destroy');

    Route::post('competitions/{competition}/results', [MatchResultController::class, 'store'])->name('competitions.results.store');

    Route::get('my-competitions', [UserCompetitionController::class, 'index'])->name('my-competitions.index');
    Route::get('my-competitions/{competition}', [UserCompetitionController::class, 'show'])->name('my-competitions.show');
});
