<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\ValueObjects\ScheduleConflict;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Collection;

/**
 * Detects scheduling conflicts across all competitions of an event.
 *
 * Three classes of conflict:
 *   1. Player double-booking — same user is a member of two competitions
 *      whose stage schedules overlap in time.
 *   2. Out-of-window — a stage extends past the event's start/end window.
 *   3. Sequence violation — within one competition, stage N+1 starts before
 *      stage N's run + reserve completes.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-004
 */
class ConflictDetector
{
    /**
     * @return Collection<int, ScheduleConflict>
     */
    public function detectForEvent(Event $event): Collection
    {
        $schedules = CompetitionStageSchedule::query()
            ->whereHas('competition', fn ($q) => $q->where('event_id', $event->id))
            ->whereNotNull('starts_at')
            ->with('competition.teams.activeMembers')
            ->get();

        $conflicts = collect();

        $conflicts = $conflicts->merge($this->detectOutOfWindow($schedules, $event));
        $conflicts = $conflicts->merge($this->detectSequenceViolations($schedules));
        $conflicts = $conflicts->merge($this->detectPlayerOverlaps($schedules));

        return $conflicts->values();
    }

    /**
     * @param  Collection<int, CompetitionStageSchedule>  $schedules
     * @return Collection<int, ScheduleConflict>
     */
    private function detectOutOfWindow(Collection $schedules, Event $event): Collection
    {
        $eventStart = $event->start_date;
        $eventEnd = $event->end_date;

        return $schedules
            ->filter(function (CompetitionStageSchedule $s) use ($eventStart, $eventEnd): bool {
                $end = $s->endsAt();
                if ($end === null) {
                    return false;
                }
                if ($eventStart !== null && $s->starts_at !== null && $s->starts_at->lt($eventStart)) {
                    return true;
                }
                if ($eventEnd !== null && $end->gt($eventEnd)) {
                    return true;
                }

                return false;
            })
            ->map(fn (CompetitionStageSchedule $s) => new ScheduleConflict(
                severity: ScheduleConflict::SEVERITY_ERROR,
                messageKey: 'competition_board.conflicts.out_of_event_window',
                params: ['stage' => $s->stage_name],
                scheduleIds: [$s->id],
            ));
    }

    /**
     * @param  Collection<int, CompetitionStageSchedule>  $schedules
     * @return Collection<int, ScheduleConflict>
     */
    private function detectSequenceViolations(Collection $schedules): Collection
    {
        $byCompetition = $schedules->groupBy('competition_id');
        $out = collect();

        foreach ($byCompetition as $stages) {
            $ordered = $stages->sortBy('sequence')->values();
            for ($i = 0; $i < $ordered->count() - 1; $i++) {
                /** @var CompetitionStageSchedule $current */
                $current = $ordered[$i];
                /** @var CompetitionStageSchedule $next */
                $next = $ordered[$i + 1];
                $currentEnd = $current->endsAt();
                if ($currentEnd === null || $next->starts_at === null) {
                    continue;
                }
                if ($next->starts_at->lt($currentEnd)) {
                    $out->push(new ScheduleConflict(
                        severity: ScheduleConflict::SEVERITY_WARNING,
                        messageKey: 'competition_board.conflicts.stage_sequence_overlap',
                        params: [
                            'earlier' => $current->stage_name,
                            'later' => $next->stage_name,
                        ],
                        scheduleIds: [$current->id, $next->id],
                    ));
                }
            }
        }

        return $out;
    }

    /**
     * Player double-booking. Two schedules conflict if their (run + reserve)
     * windows overlap AND they share at least one active player.
     *
     * @param  Collection<int, CompetitionStageSchedule>  $schedules
     * @return Collection<int, ScheduleConflict>
     */
    private function detectPlayerOverlaps(Collection $schedules): Collection
    {
        $userIdsByCompetition = $this->resolveUserIdsByCompetition(
            $schedules->pluck('competition_id')->unique()->all(),
        );

        $list = $schedules->values()->all();
        $out = collect();

        for ($i = 0; $i < count($list); $i++) {
            $a = $list[$i];
            $aEnd = $a->endsAt();
            if ($aEnd === null) {
                continue;
            }
            for ($j = $i + 1; $j < count($list); $j++) {
                $b = $list[$j];
                if ($a->competition_id === $b->competition_id) {
                    continue;
                }
                $bEnd = $b->endsAt();
                if ($bEnd === null) {
                    continue;
                }
                $overlap = $a->starts_at->lt($bEnd) && $b->starts_at->lt($aEnd);
                if (! $overlap) {
                    continue;
                }
                $aUsers = $userIdsByCompetition[$a->competition_id] ?? [];
                $bUsers = $userIdsByCompetition[$b->competition_id] ?? [];
                $shared = array_intersect($aUsers, $bUsers);
                if ($shared === []) {
                    continue;
                }
                $out->push(new ScheduleConflict(
                    severity: ScheduleConflict::SEVERITY_ERROR,
                    messageKey: 'competition_board.conflicts.player_double_booked',
                    params: [
                        'count' => count($shared),
                        'stage_a' => $a->stage_name,
                        'stage_b' => $b->stage_name,
                    ],
                    scheduleIds: [$a->id, $b->id],
                ));
            }
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $competitionIds
     * @return array<string, array<int, int>>
     */
    private function resolveUserIdsByCompetition(array $competitionIds): array
    {
        if ($competitionIds === []) {
            return [];
        }

        $teamIdsByCompetition = CompetitionTeam::query()
            ->whereIn('competition_id', $competitionIds)
            ->get(['id', 'competition_id'])
            ->groupBy('competition_id')
            ->map(fn ($teams) => $teams->pluck('id')->all())
            ->all();

        $allTeamIds = array_merge(...array_values($teamIdsByCompetition));

        if ($allTeamIds === []) {
            return [];
        }

        $usersByTeam = CompetitionTeamMember::query()
            ->whereIn('team_id', $allTeamIds)
            ->whereNull('left_at')
            ->get(['team_id', 'user_id'])
            ->groupBy('team_id')
            ->map(fn ($members) => $members->pluck('user_id')->map(fn ($id) => (int) $id)->all())
            ->all();

        $result = [];
        foreach ($teamIdsByCompetition as $competitionId => $teamIds) {
            $merged = [];
            foreach ($teamIds as $teamId) {
                foreach ($usersByTeam[$teamId] ?? [] as $userId) {
                    $merged[$userId] = true;
                }
            }
            $result[$competitionId] = array_keys($merged);
        }

        return $result;
    }
}
