<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\TeamInvite;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AcceptTeamInvite
{
    public function __construct(private readonly JoinTeam $joinTeam) {}

    public function execute(TeamInvite $invite, User $user): void
    {
        if (! $invite->isPending()) {
            throw ValidationException::withMessages([
                'invite' => 'This invite has already been used or has expired.',
            ]);
        }

        $team = $invite->team;

        if ($team->isFull()) {
            throw ValidationException::withMessages([
                'team' => 'This team is already full.',
            ]);
        }

        if ($team->hasMember($user)) {
            throw ValidationException::withMessages([
                'team' => 'You are already a member of this team.',
            ]);
        }

        $competition = $team->competition;

        if (! $this->hasValidTicketForEvent($user, $competition)) {
            throw ValidationException::withMessages([
                'ticket' => 'You need a valid ticket for this event to join a team.',
            ]);
        }

        $this->joinTeam->execute($team, $user);

        $invite->update([
            'accepted_at' => now(),
            'user_id' => $user->id,
        ]);
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
