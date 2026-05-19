<?php

namespace App\Domain\CompetitionSchedule\Http\Controllers;

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Events\StageScheduleUpdated;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\Services\ConflictDetector;
use App\Domain\CompetitionSchedule\Services\DurationEstimator;
use App\Domain\CompetitionSchedule\Services\StageScheduleSynchronizer;
use App\Domain\Event\Models\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-001, COMP-SCH-006
 */
class CompetitionBoardController extends Controller
{
    public function __construct(
        private readonly ConflictDetector $conflictDetector,
        private readonly DurationEstimator $estimator,
        private readonly StageScheduleSynchronizer $synchronizer,
    ) {}

    public function show(Event $event): Response
    {
        $this->authorize('viewBoard', [CompetitionStageSchedule::class, $event]);

        return Inertia::render('events/CompetitionBoard', $this->buildPayload($event));
    }

    public function showCurrent(Request $request): Response|RedirectResponse
    {
        $event = $this->resolveSelectedEvent($request);
        if ($event === null) {
            return redirect()->route('dashboard')->with('status', 'competition_board.no_event_selected');
        }

        return $this->show($event);
    }

    public function dataCurrent(Request $request): JsonResponse
    {
        $event = $this->resolveSelectedEvent($request);
        if ($event === null) {
            return response()->json(['data' => null], 404);
        }

        return $this->data($event);
    }

    public function syncCurrent(Request $request): RedirectResponse
    {
        $event = $this->resolveSelectedEvent($request);
        if ($event === null) {
            return back();
        }

        return $this->sync($event);
    }

    public function autoFitCurrent(Request $request): RedirectResponse
    {
        $event = $this->resolveSelectedEvent($request);
        if ($event === null) {
            return back();
        }

        return $this->autoFit($event);
    }

    private function resolveSelectedEvent(Request $request): ?Event
    {
        $id = $request->session()->get('selected_event_id');
        if (! $id) {
            return null;
        }

        return Event::find($id);
    }

    public function data(Event $event): JsonResponse
    {
        $this->authorize('viewBoard', [CompetitionStageSchedule::class, $event]);

        return response()->json($this->buildPayload($event));
    }

    public function sync(Event $event): RedirectResponse
    {
        $this->authorize('viewBoard', [CompetitionStageSchedule::class, $event]);

        $competitions = Competition::query()
            ->where('event_id', $event->id)
            ->whereNotNull('lanbrackets_id')
            ->get();

        foreach ($competitions as $competition) {
            $this->synchronizer->syncCompetition($competition);
        }

        StageScheduleUpdated::dispatch($event->id, '');

        return back()->with('status', 'competition_board.sync.queued');
    }

    public function autoFit(Event $event): RedirectResponse
    {
        $this->authorize('viewBoard', [CompetitionStageSchedule::class, $event]);

        $competitions = Competition::query()
            ->where('event_id', $event->id)
            ->with('stageSchedules')
            ->get();

        foreach ($competitions as $competition) {
            $cursor = $competition->starts_at?->copy() ?? $event->start_date?->copy() ?? Carbon::now();
            foreach ($competition->stageSchedules as $schedule) {
                if ($schedule->starts_at !== null) {
                    $cursor = $schedule->endsAt() ?? $cursor;

                    continue;
                }
                $schedule->update(['starts_at' => $cursor->copy()]);
                $cursor = $cursor->copy()->addMinutes(
                    $schedule->estimated_duration_minutes + $schedule->reserve_buffer_minutes
                );
            }
        }

        StageScheduleUpdated::dispatch($event->id, '');

        return back()->with('status', 'competition_board.auto_fit.applied');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Event $event): array
    {
        $competitions = Competition::query()
            ->where('event_id', $event->id)
            ->with(['stageSchedules', 'game:id,name,avg_match_minutes'])
            ->get();

        $competitionsPayload = $competitions
            ->map(function (Competition $competition): array {
                $ordered = $competition->stageSchedules->values();

                $stages = $ordered->map(function (CompetitionStageSchedule $s, int $idx) use ($ordered, $competition): array {
                    $endsAt = $s->endsAt();
                    $next = $ordered->get($idx + 1);

                    $slackMinutes = null;
                    $slackAgainst = null;
                    if ($endsAt !== null) {
                        if ($next !== null && $next->starts_at !== null) {
                            $slackMinutes = (int) round($next->starts_at->diffInMinutes($endsAt, false));
                            $slackAgainst = 'next_stage';
                        } elseif ($competition->ends_at !== null) {
                            $slackMinutes = (int) round($competition->ends_at->diffInMinutes($endsAt, false));
                            $slackAgainst = 'competition_end';
                        }
                    }

                    return [
                        'id' => $s->id,
                        'lanbrackets_stage_id' => $s->lanbrackets_stage_id,
                        'stage_name' => $s->stage_name,
                        'stage_type' => $s->stage_type,
                        'sequence' => $s->sequence,
                        'starts_at' => $s->starts_at?->toIso8601String(),
                        'estimated_duration_minutes' => $s->estimated_duration_minutes,
                        'reserve_buffer_minutes' => $s->reserve_buffer_minutes,
                        'duration_overridden' => $s->duration_overridden,
                        'notes' => $s->notes,
                        'ends_at' => $endsAt?->toIso8601String(),
                        'slack_minutes' => $slackMinutes,
                        'slack_against' => $slackAgainst,
                    ];
                })->all();

                $totalMinutes = collect($stages)->sum(fn (array $s) => $s['estimated_duration_minutes'] + $s['reserve_buffer_minutes']);

                // Competition-level current slack: forward-mapped approximation
                // of the spec's backwards-mapped slack. = competition.ends_at − end-of-last-stage.
                // Positive ⇒ ahead of plan; negative ⇒ projected to overrun.
                $currentSlackMinutes = null;
                $lastStage = $ordered->last();
                if ($lastStage !== null && $competition->ends_at !== null) {
                    $lastEnd = $lastStage->endsAt();
                    if ($lastEnd !== null) {
                        $currentSlackMinutes = (int) round($competition->ends_at->diffInMinutes($lastEnd, false));
                    }
                }

                return [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'status' => $competition->status?->value,
                    'starts_at' => $competition->starts_at?->toIso8601String(),
                    'ends_at' => $competition->ends_at?->toIso8601String(),
                    'game_name' => $competition->game?->name,
                    'stage_schedules' => $stages,
                    'total_minutes' => $totalMinutes,
                    'current_slack_minutes' => $currentSlackMinutes,
                ];
            })
            // Longest competitions first; tie-break on starts_at.
            ->sortBy([
                ['total_minutes', 'desc'],
                ['starts_at', 'asc'],
            ])
            ->values()
            ->all();

        $conflicts = $this->conflictDetector->detectForEvent($event)
            ->map(fn ($c) => $c->toArray())
            ->all();

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_date' => $event->start_date?->toIso8601String(),
                'end_date' => $event->end_date?->toIso8601String(),
            ],
            'competitions' => $competitionsPayload,
            'conflicts' => $conflicts,
        ];
    }
}
