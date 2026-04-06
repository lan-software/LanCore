<?php

use App\Domain\Competition\Http\Controllers\AdminTeamController;
use App\Domain\Competition\Http\Controllers\CompetitionController;
use App\Domain\Competition\Http\Controllers\MatchResultController;
use App\Domain\Competition\Http\Controllers\TeamController;
use App\Domain\Competition\Http\Controllers\TeamInviteController;
use App\Domain\Competition\Http\Controllers\UserCompetitionController;
use App\Domain\Competition\Http\Controllers\UserTeamController;
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
    Route::post('competitions/{competition}/teams/{team}/request', [TeamController::class, 'requestJoin'])->name('competitions.teams.request-join');
    Route::post('competitions/{competition}/teams/{team}/invite', [TeamController::class, 'invite'])->name('competitions.teams.invite');
    Route::post('competitions/{competition}/teams/{team}/leave', [TeamController::class, 'leave'])->name('competitions.teams.leave');
    Route::delete('competitions/{competition}/teams/{team}', [TeamController::class, 'destroy'])->name('competitions.teams.destroy');

    // Admin: Team management
    Route::get('admin/teams', [AdminTeamController::class, 'index'])->name('admin.teams.index');
    Route::get('admin/teams/{team}', [AdminTeamController::class, 'edit'])->name('admin.teams.edit');
    Route::patch('admin/teams/{team}', [AdminTeamController::class, 'update'])->name('admin.teams.update');
    Route::delete('admin/teams/{team}/members/{member}', [AdminTeamController::class, 'removeMember'])->name('admin.teams.remove-member');

    Route::post('teams/join-requests/{joinRequest}/resolve', [TeamController::class, 'resolveRequest'])->name('teams.join-requests.resolve');

    Route::get('team-invites/{token}', [TeamInviteController::class, 'show'])->name('team-invites.show');
    Route::post('team-invites/{token}/accept', [TeamInviteController::class, 'accept'])->name('team-invites.accept');
    Route::post('team-invites/{token}/decline', [TeamInviteController::class, 'decline'])->name('team-invites.decline');

    Route::post('competitions/{competition}/results', [MatchResultController::class, 'store'])->name('competitions.results.store');

    Route::get('my-competitions', [UserCompetitionController::class, 'index'])->name('my-competitions.index');
    Route::get('my-competitions/{competition}', [UserCompetitionController::class, 'show'])->name('my-competitions.show');

    Route::get('my-teams', [UserTeamController::class, 'index'])->name('my-teams.index');
    Route::get('my-teams/{team}', [UserTeamController::class, 'show'])->name('my-teams.show');
});
