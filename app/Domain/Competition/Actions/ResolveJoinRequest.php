<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\TeamJoinRequest;
use App\Domain\Competition\Notifications\JoinRequestResolvedNotification;
use App\Models\User;

class ResolveJoinRequest
{
    public function __construct(private readonly JoinTeam $joinTeam) {}

    public function approve(TeamJoinRequest $request, User $resolver): void
    {
        $request->update([
            'status' => 'approved',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        $this->joinTeam->execute($request->team, $request->user);

        $request->user->notify(new JoinRequestResolvedNotification($request->load('team.competition'), true));
    }

    public function deny(TeamJoinRequest $request, User $resolver): void
    {
        $request->update([
            'status' => 'denied',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
        ]);

        $request->user->notify(new JoinRequestResolvedNotification($request->load('team.competition'), false));
    }
}
