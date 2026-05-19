<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\ValueObjects\MatchProposal;
use App\Domain\Event\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Builds the live "what should we play next" queue for an event.
 *
 * Pulls pending matches from every live competition via LanBrackets,
 * filters out those whose dependencies haven't resolved, scores them
 * by urgency + scheduled window, and marks any whose participants are
 * already mid-match elsewhere as blocked.
 *
 * Result is cached per-event for 30 seconds.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-005
 */
class NextMatchProposer
{
    public function __construct(
        private readonly LanBracketsClient $client,
    ) {}

    /**
     * Player-facing variant: only matches the given user is participating in,
     * across competitions of the given event. Mirrors the spec's "Play Next"
     * card (competition-board.md → Player-facing UX).
     *
     * @return Collection<int, MatchProposal>
     */
    public function proposeForUser(User $user, Event $event, ?CarbonInterface $now = null, int $limit = 5): Collection
    {
        $teamIds = CompetitionTeamMember::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->whereHas('team.competition', fn ($q) => $q->where('event_id', $event->id))
            ->pluck('team_id')
            ->all();

        if ($teamIds === []) {
            return collect();
        }

        return $this->proposeForEvent($event, $now, 100)
            ->filter(function (MatchProposal $p) use ($teamIds): bool {
                foreach ($p->participants as $part) {
                    $teamId = $part['team_id'] ?? $part['external_reference_id'] ?? null;
                    if ($teamId !== null && in_array((string) $teamId, array_map('strval', $teamIds), true)) {
                        return true;
                    }
                }

                return false;
            })
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, MatchProposal>
     */
    public function proposeForEvent(Event $event, ?CarbonInterface $now = null, int $limit = 20): Collection
    {
        $now ??= Carbon::now();

        $competitions = Competition::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [
                CompetitionStatus::Running->value,
                CompetitionStatus::RegistrationClosed->value,
            ])
            ->whereNotNull('lanbrackets_id')
            ->with('stageSchedules')
            ->get();

        if ($competitions->isEmpty()) {
            return collect();
        }

        $allMatches = collect();
        $inProgressParticipantIds = [];

        foreach ($competitions as $competition) {
            foreach ($competition->stageSchedules as $schedule) {
                $matches = $this->fetchMatches($competition, $schedule);
                foreach ($matches as $m) {
                    if (($m['status'] ?? '') === 'in_progress') {
                        foreach ($m['participants'] ?? [] as $p) {
                            $pid = $p['competition_participant_id'] ?? $p['participant_id'] ?? null;
                            if ($pid !== null) {
                                $inProgressParticipantIds[(string) $pid] = true;
                            }
                        }
                    }
                }
                $allMatches->push([
                    'competition' => $competition,
                    'schedule' => $schedule,
                    'matches' => $matches,
                ]);
            }
        }

        $proposals = collect();

        foreach ($allMatches as $bucket) {
            /** @var Competition $competition */
            $competition = $bucket['competition'];
            /** @var CompetitionStageSchedule $schedule */
            $schedule = $bucket['schedule'];

            foreach ($bucket['matches'] as $match) {
                if (! $this->isCandidate($match)) {
                    continue;
                }

                $participantIds = [];
                $participantsForDisplay = [];
                foreach ($match['participants'] ?? [] as $p) {
                    $pid = $p['competition_participant_id'] ?? $p['participant_id'] ?? null;
                    if ($pid !== null) {
                        $participantIds[] = (string) $pid;
                    }
                    $participantsForDisplay[] = [
                        'participant_id' => $pid,
                        'name' => $p['name'] ?? $p['team_name'] ?? $p['participant_name'] ?? null,
                        // Per COMP-F-018, LanBrackets carries the LanCore CompetitionTeam.id
                        // as external_reference_id on participant slots, so we can map back.
                        'team_id' => $p['external_reference_id'] ?? null,
                    ];
                }

                $blocked = false;
                foreach ($participantIds as $pid) {
                    if (isset($inProgressParticipantIds[$pid])) {
                        $blocked = true;
                        break;
                    }
                }

                [$score, $reasons] = $this->scoreMatch($schedule, $competition, $now, $blocked);

                $proposals->push(new MatchProposal(
                    competitionId: $competition->id,
                    competitionName: $competition->name,
                    stageId: $schedule->lanbrackets_stage_id,
                    stageName: $schedule->stage_name,
                    matchId: $match['id'] ?? 0,
                    score: $score,
                    blocked: $blocked,
                    participants: $participantsForDisplay,
                    reasonKeys: $reasons,
                ));
            }
        }

        return $proposals
            ->sortByDesc(fn (MatchProposal $p) => ($p->blocked ? -1000 : 0) + $p->score)
            ->values()
            ->take($limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchMatches(Competition $competition, CompetitionStageSchedule $schedule): array
    {
        $key = "next-match-proposer:matches:{$competition->id}:{$schedule->lanbrackets_stage_id}";

        return Cache::remember($key, 30, function () use ($competition, $schedule): array {
            try {
                return $this->client->getMatches(
                    (string) $competition->lanbrackets_id,
                    $schedule->lanbrackets_stage_id,
                );
            } catch (Throwable) {
                return [];
            }
        });
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function isCandidate(array $match): bool
    {
        $status = (string) ($match['status'] ?? '');
        if (! in_array($status, ['pending', 'scheduled'], true)) {
            return false;
        }

        // Reject if any incoming connection feeder is not finished.
        foreach (($match['incoming_connections'] ?? []) as $conn) {
            if (($conn['from_match']['status'] ?? null) !== 'finished') {
                return false;
            }
        }

        // Both participants must be set.
        $participants = $match['participants'] ?? [];
        if (count($participants) < 2) {
            return false;
        }
        foreach ($participants as $p) {
            $pid = $p['competition_participant_id'] ?? $p['participant_id'] ?? null;
            if ($pid === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0:int,1:array<int,string>}
     */
    private function scoreMatch(
        CompetitionStageSchedule $schedule,
        Competition $competition,
        CarbonInterface $now,
        bool $blocked,
    ): array {
        $score = 0;
        $reasons = [];

        if ($schedule->starts_at !== null) {
            $runEnd = $schedule->activeRunEndsAt();
            $bufferEnd = $schedule->endsAt();
            if ($schedule->starts_at->lte($now) && $bufferEnd !== null && $bufferEnd->gte($now)) {
                $score += 100;
                $reasons[] = 'competition_board.proposal.in_window';
            } elseif ($schedule->starts_at->gt($now)) {
                $minutesUntil = $schedule->starts_at->diffInMinutes($now, true);
                $score += max(0, 60 - (int) $minutesUntil);
                $reasons[] = 'competition_board.proposal.upcoming';
            } else {
                $score += 50;
                $reasons[] = 'competition_board.proposal.overrunning';
            }
        } else {
            $score += 20;
            $reasons[] = 'competition_board.proposal.unscheduled';
        }

        if ($competition->ends_at !== null && $competition->ends_at->lt($now->copy()->addHour())) {
            $score += 25;
            $reasons[] = 'competition_board.proposal.deadline_close';
        }

        if ($blocked) {
            $reasons[] = 'competition_board.proposal.blocked_team_busy';
        } else {
            $reasons[] = 'competition_board.proposal.teams_free';
        }

        return [$score, $reasons];
    }
}
