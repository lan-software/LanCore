<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\TeamJoinRequest;
use App\Domain\Competition\Notifications\TeamJoinRequestNotification;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestToJoinTeam
{
    public function execute(CompetitionTeam $team, User $user, ?string $message = null): TeamJoinRequest
    {
        $competition = $team->competition;

        if (! $this->hasValidTicketForEvent($user, $competition)) {
            throw ValidationException::withMessages([
                'ticket' => 'You need a valid ticket for this event to join a team.',
            ]);
        }

        $existingPending = $team->joinRequests()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'request' => 'You already have a pending join request for this team.',
            ]);
        }

        $request = TeamJoinRequest::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'message' => $message,
        ]);

        if ($team->captain) {
            $team->captain->notify(new TeamJoinRequestNotification($request->load('user', 'team.competition')));
        }

        return $request;
    }

    private function hasValidTicketForEvent(User $user, \App\Domain\Competition\Models\Competition $competition): bool
    {
        if (! $competition->event_id) {
            return true;
        }

        $event = $competition->event;

        if ($event?->end_date && $event->end_date->isPast()) {
            return true;
        }

        return $user->assignedTickets()->where('event_id', $competition->event_id)->exists()
            || $user->ownedTickets()->where('event_id', $competition->event_id)->exists();
    }
}
