<?php

namespace App\Domain\CompetitionSchedule\Http\Controllers;

use App\Domain\CompetitionSchedule\Services\NextMatchProposer;
use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Player-facing "Play Next" recommendation surface.
 *
 * Spec home: competition-board.md → "Player-facing UX (v1.b)". This is
 * primarily for signed-in competition participants, not organizers.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-005
 */
class NextMatchProposalController extends Controller
{
    public function __construct(
        private readonly NextMatchProposer $proposer,
    ) {}

    public function forCurrentUser(Request $request): Response
    {
        $user = $request->user();
        $event = $this->resolveMyEvent($request);

        $proposals = ($user !== null && $event !== null)
            ? $this->proposer
                ->proposeForUser($user, $event)
                ->map(fn ($p) => $p->toArray())
                ->all()
            : [];

        return Inertia::render('portal/PlayNext', [
            'event' => $event ? [
                'id' => $event->id,
                'name' => $event->name,
            ] : null,
            'proposals' => $proposals,
        ]);
    }

    private function resolveMyEvent(Request $request): ?Event
    {
        $id = $request->session()->get('my_selected_event_id')
            ?? $request->session()->get('selected_event_id');

        if (! $id) {
            return null;
        }

        return Event::find($id);
    }
}
