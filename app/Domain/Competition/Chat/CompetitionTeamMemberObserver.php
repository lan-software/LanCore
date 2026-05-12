<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Competition\Models\CompetitionTeamMember;

/**
 * Auto-joins users to the competition's chat room when they join a team.
 * Also covers the match-room slot if a per-match room already exists.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-029
 */
class CompetitionTeamMemberObserver
{
    public function __construct(private readonly CompetitionRoomAutoJoin $autoJoin) {}

    public function created(CompetitionTeamMember $member): void
    {
        $competitionId = $member->team?->competition_id;

        if ($competitionId === null) {
            return;
        }

        $competitionRoom = ChatRoom::query()
            ->where('key', "competition:{$competitionId}")
            ->first();

        if ($competitionRoom !== null) {
            $this->autoJoin->joinUsers($competitionRoom, [$member->user_id]);
        }
    }
}
