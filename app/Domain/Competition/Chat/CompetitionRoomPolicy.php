<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\Permission as ChatPermission;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Competition\Enums\Permission as CompetitionPermission;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

/**
 * RoomPolicy for the competition-wide chat room (key: `competition:{id}`).
 *
 * @see docs/mil-std-498/SRS.md CHT-F-027
 */
class CompetitionRoomPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        $competitionId = $this->competitionIdFromKey($room->key);

        if ($competitionId === null) {
            return false;
        }

        if ($user->hasPermission(CompetitionPermission::ManageCompetitions)
            || $user->hasPermission(ChatPermission::ModerateChat)
        ) {
            return true;
        }

        return CompetitionTeamMember::query()
            ->whereHas('team', fn ($q) => $q->where('competition_id', $competitionId))
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return $this->canView($user, $room) && $room->status === RoomStatus::Open;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return $user->hasPermission(CompetitionPermission::ManageCompetitions)
            || $user->hasPermission(ChatPermission::ModerateChat);
    }

    public function describe(ChatRoom $room): string
    {
        $competitionId = $this->competitionIdFromKey($room->key);
        $competition = $competitionId !== null ? Competition::find($competitionId) : null;

        return $competition?->name ?? 'Competition chat';
    }

    private function competitionIdFromKey(string $key): ?int
    {
        if (preg_match('/^competition:(\d+)$/', $key, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
