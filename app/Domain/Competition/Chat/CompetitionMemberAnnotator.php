<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Competition\Enums\Permission as CompetitionPermission;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

/**
 * Enriches a chat member list with competition-specific indicators:
 *
 *   - `is_admin`   — true when the user holds `ManageCompetitions` globally.
 *   - `team_name`  — the team the user belongs to in this competition.
 *   - `team_tag`   — short tag for compact rendering.
 *
 * Skips the team fields when `team_size = 1`, since a one-person "team" is
 * just the user themselves and a team badge adds no signal there.
 *
 * Chat is consumer-agnostic, so this enrichment lives in the Competition
 * domain rather than `App\Domain\Chat`. Other consumers can add their own
 * annotators if they need similar context.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-034
 */
class CompetitionMemberAnnotator
{
    /**
     * @param  array<int, array<string, mixed>>  $members
     * @return array<int, array<string, mixed>>
     */
    public function annotate(string $roomKey, array $members): array
    {
        $competitionId = $this->competitionIdFromKey($roomKey);

        if ($competitionId === null) {
            return $members;
        }

        $competition = Competition::query()->find($competitionId, ['id', 'team_size']);

        if ($competition === null) {
            return $members;
        }

        $userIds = collect($members)->pluck('user_id')->filter()->all();

        if ($userIds === []) {
            return $members;
        }

        $adminUserIds = $this->resolveAdminUserIds($userIds);

        $teamByUserId = [];
        $showTeam = ($competition->team_size ?? 1) > 1;

        if ($showTeam) {
            $teamByUserId = CompetitionTeamMember::query()
                ->whereIn('user_id', $userIds)
                ->whereNull('left_at')
                ->whereHas('team', fn ($q) => $q->where('competition_id', $competitionId))
                ->with('team:id,name,tag')
                ->get(['team_id', 'user_id'])
                ->keyBy('user_id')
                ->map(fn ($m) => [
                    'team_id' => $m->team_id,
                    'team_name' => $m->team?->name,
                    'team_tag' => $m->team?->tag,
                ])
                ->all();
        }

        return array_map(function (array $member) use ($adminUserIds, $teamByUserId, $showTeam): array {
            $userId = (int) ($member['user_id'] ?? 0);

            $member['is_admin'] = in_array($userId, $adminUserIds, true);

            if ($showTeam) {
                $team = $teamByUserId[$userId] ?? null;
                $member['team_id'] = $team['team_id'] ?? null;
                $member['team_name'] = $team['team_name'] ?? null;
                $member['team_tag'] = $team['team_tag'] ?? null;
            }

            return $member;
        }, $members);
    }

    private function competitionIdFromKey(string $key): ?int
    {
        if (preg_match('/^competition:(\d+)(?::|$)/', $key, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function resolveAdminUserIds(array $userIds): array
    {
        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id'])
            ->filter(fn (User $u) => $u->hasPermission(CompetitionPermission::ManageCompetitions))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
