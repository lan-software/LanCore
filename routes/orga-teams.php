<?php

use App\Domain\OrgaTeam\Http\Controllers\EventOrgaTeamController;
use App\Domain\OrgaTeam\Http\Controllers\OrgaSubTeamController;
use App\Domain\OrgaTeam\Http\Controllers\OrgaTeamController;
use App\Domain\OrgaTeam\Http\Controllers\PublicOrgaTeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('orga-teams', [OrgaTeamController::class, 'index'])->name('orga-teams.index');
    Route::get('orga-teams/create', [OrgaTeamController::class, 'create'])->name('orga-teams.create');
    Route::post('orga-teams', [OrgaTeamController::class, 'store'])->name('orga-teams.store');
    Route::get('orga-teams/{orgaTeam}', [OrgaTeamController::class, 'edit'])->name('orga-teams.edit');
    Route::patch('orga-teams/{orgaTeam}', [OrgaTeamController::class, 'update'])->name('orga-teams.update');
    Route::delete('orga-teams/{orgaTeam}', [OrgaTeamController::class, 'destroy'])->name('orga-teams.destroy');

    Route::post('orga-teams/{orgaTeam}/sub-teams', [OrgaSubTeamController::class, 'store'])->name('orga-teams.sub-teams.store');
    Route::patch('orga-sub-teams/{subTeam}', [OrgaSubTeamController::class, 'update'])->name('orga-sub-teams.update');
    Route::delete('orga-sub-teams/{subTeam}', [OrgaSubTeamController::class, 'destroy'])->name('orga-sub-teams.destroy');
    Route::patch('orga-sub-teams/{subTeam}/members', [OrgaSubTeamController::class, 'syncMembers'])->name('orga-sub-teams.members.sync');

    Route::patch('events/{event}/orga-team', [EventOrgaTeamController::class, 'update'])->name('events.orga-team.update');
});

Route::get('events/{event}/orga-team', [PublicOrgaTeamController::class, 'show'])->name('events.orga-team.show');
