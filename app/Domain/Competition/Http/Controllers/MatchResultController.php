<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Actions\SubmitMatchResult;
use App\Domain\Competition\Http\Requests\SubmitMatchResultRequest;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\MatchResultProof;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class MatchResultController extends Controller
{
    public function __construct(private readonly SubmitMatchResult $submitMatchResult) {}

    public function store(SubmitMatchResultRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('create', [MatchResultProof::class, $competition]);

        $user = $request->user();
        $team = $competition->teams()
            ->whereHas('activeMembers', fn ($q) => $q->where('user_id', $user->id))
            ->first();

        $this->submitMatchResult->execute(
            competition: $competition,
            lanbracketsMatchId: $request->validated('lanbrackets_match_id'),
            scores: $request->validated('scores'),
            screenshot: $request->file('screenshot'),
            user: $user,
            team: $team,
        );

        return back();
    }
}
