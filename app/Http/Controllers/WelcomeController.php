<?php

namespace App\Http\Controllers;

use App\Domain\Announcement\Models\Announcement;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Event\Models\Event;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Seating\Http\Resources\SeatPlanResource;
use App\Domain\Seating\Models\SeatAssignment;
use App\Support\StorageRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $nextEvent = Event::published()
            ->upcoming()
            ->with([
                'venue.address',
                'venue.images',
                'programs' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('sort_order'),
                'programs.sponsors',
                'programs.timeSlots' => fn ($q) => $q->where('visibility', ProgramVisibility::Public)->orderBy('starts_at'),
                'programs.timeSlots.sponsors',
                'sponsors.sponsorLevel',
                'seatPlans.blocks.seats',
                'seatPlans.blocks.labels',
                'seatPlans.blocks.categoryRestrictions',
                'seatPlans.globalLabels',
            ])
            ->orderBy('start_date')
            ->first();

        $nextEventData = null;

        if ($nextEvent) {
            $nextEventData = $nextEvent->toArray();
            $nextEventData['seat_plans'] = SeatPlanResource::collection($nextEvent->seatPlans)->resolve();
            $bannerImages = array_values(array_filter($nextEvent->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
            $nextEventData['banner_images'] = $bannerImages;
            $nextEventData['banner_image_urls'] = array_map(
                fn (string $path) => StorageRole::publicUrl($path),
                $bannerImages,
            );

            if (isset($nextEventData['venue']['images'])) {
                $nextEventData['venue']['images'] = collect($nextEventData['venue']['images'])->map(function (array $image) {
                    $image['url'] = StorageRole::publicUrl($image['path']);

                    return $image;
                })->all();
            }

            if (isset($nextEventData['sponsors'])) {
                $nextEventData['sponsors'] = collect($nextEventData['sponsors'])->map(function (array $sponsor) {
                    $sponsor['logo_url'] = $sponsor['logo'] ? StorageRole::publicUrl($sponsor['logo']) : null;

                    return $sponsor;
                })->all();
            }

            $nextEventData['taken_seats'] = $this->getTakenSeats($nextEvent, $request);
        }

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'nextEvent' => $nextEventData,
            'latestNews' => $this->getLatestNews(),
            'announcements' => $nextEvent ? $this->getActiveAnnouncements($nextEvent, $request) : [],
            'dismissedAnnouncementIds' => $nextEvent ? $this->getDismissedAnnouncementIds($nextEvent, $request) : [],
            'openCompetitions' => Competition::query()
                ->where('status', CompetitionStatus::RegistrationOpen)
                ->with(['game:id,name,slug', 'event:id,name'])
                ->withCount('teams')
                ->orderBy('registration_closes_at')
                ->get()
                ->map(fn (Competition $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                    'type' => $c->type->value,
                    'stage_type' => $c->stage_type?->value,
                    'team_size' => $c->team_size,
                    'max_teams' => $c->max_teams,
                    'teams_count' => $c->teams_count,
                    'game' => $c->game ? ['name' => $c->game->name] : null,
                    'event' => $c->event ? ['name' => $c->event->name] : null,
                    'registration_closes_at' => $c->registration_closes_at?->toIso8601String(),
                    'starts_at' => $c->starts_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLatestNews(): array
    {
        $articles = NewsArticle::published()
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return $articles->map(function (NewsArticle $article) {
            $data = $article->toArray();
            $data['image_url'] = $article->image ? StorageRole::publicUrl($article->image) : null;

            return $data;
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveAnnouncements(Event $event, Request $request): array
    {
        $query = Announcement::query()
            ->where('event_id', $event->id)
            ->published()
            ->with('author:id,name')
            ->orderByDesc('published_at');

        if ($request->user()) {
            $query->notDismissedBy($request->user());
        }

        return $query->get()->toArray();
    }

    /**
     * Build the per-seat occupancy overlay shown on the public seat plan.
     *
     * Each entry maps a seat back to its assigned attendee, with the name redacted
     * unless the viewer is allowed to see it (per User::isSeatNameVisibleTo).
     *
     * @see docs/mil-std-498/SRS.md SET-F-009, SET-F-010
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTakenSeats(Event $event, Request $request): array
    {
        $viewer = $request->user();

        return SeatAssignment::query()
            ->forEvent($event->id)
            ->with('user')
            ->get()
            ->map(function (SeatAssignment $assignment) use ($viewer, $event): array {
                $isVisible = $assignment->user->isSeatNameVisibleTo($viewer, $event);

                return [
                    'seat_plan_id' => $assignment->seat_plan_id,
                    'seat_id' => $assignment->seat_plan_seat_id,
                    'name' => $isVisible ? $assignment->user->name : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function getDismissedAnnouncementIds(Event $event, Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return $request->user()
            ->dismissedAnnouncements()
            ->where('event_id', $event->id)
            ->pluck('announcements.id')
            ->all();
    }
}
