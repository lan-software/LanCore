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
 *   - `is_referee` — true when the user is listed as a referee for this competition.
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
 * @see docs/mil-std-498/SRS.md COMP-REF-001
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

        $userIds = collect($members)
            ->pluck('user_id')
            ->filter(fn ($id): bool => is_string($id) && $id !== '')
            ->map(fn ($id): string => (string) $id)
            ->all();

        if ($userIds === []) {
            return $members;
        }

        $adminUserIds = $this->resolveAdminUserIds($userIds);
        $refereeUserIds = $this->resolveRefereeUserIds($competition->id, $userIds);

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

        return array_map(function (array $member) use ($adminUserIds, $refereeUserIds, $teamByUserId, $showTeam): array {
            $userId = isset($member['user_id']) ? (string) $member['user_id'] : '';

            $member['is_admin'] = in_array($userId, $adminUserIds, true);
            $member['is_referee'] = in_array($userId, $refereeUserIds, true);

            if ($showTeam) {
                $team = $teamByUserId[$userId] ?? null;
                $member['team_id'] = $team['team_id'] ?? null;
                $member['team_name'] = $team['team_name'] ?? null;
                $member['team_tag'] = $team['team_tag'] ?? null;
            }

            return $member;
        }, $members);
    }

    /**
     * Returns the referee user IDs for the given competition, filtered to the
     * members we're annotating.
     *
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    public function resolveRefereeUserIds(string $competitionId, array $userIds): array
    {
        return Competition::query()
            ->whereKey($competitionId)
            ->first()
            ?->referees()
            ->whereIn('users.id', $userIds)
            ->pluck('users.id')
            ->map(fn ($id): string => (string) $id)
            ->all() ?? [];
    }

    /**
     * `competition:{ulid}` — accepts ULID (26 chars) or any non-`:` prefix to
     * stay tolerant of future key formats. The legacy regex only matched
     * digits, which broke after the int-to-ULID migration.
     */
    private function competitionIdFromKey(string $key): ?string
    {
        if (preg_match('/^competition:([^:]+)(?::|$)/', $key, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    private function resolveAdminUserIds(array $userIds): array
    {
        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id'])
            ->filter(fn (User $u) => $u->hasPermission(CompetitionPermission::ManageCompetitions))
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }
}
