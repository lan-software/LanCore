<?php

namespace App\Domain\Event\Http\Controllers;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Event\Actions\BuildEventIcal;
use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Seating\Http\Resources\SeatPlanResource;
use App\Domain\Seating\Models\SeatAssignment;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-009, EVT-F-012
 */
class PublicEventController extends Controller
{
    public function index(): Response
    {
        return $this->render(false);
    }

    public function past(): Response
    {
        return $this->render(true);
    }

    private function render(bool $isPast): Response
    {

        $query = Event::published()
            ->with('venue');

        if ($isPast) {
            $query->past()->orderByDesc('start_date');
        } else {
            $query->upcoming()->orderBy('start_date');
        }

        $events = $query->paginate(12)->withQueryString();

        $events->through(function (Event $event) {
            $eventData = $event->toArray();
            $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
            $eventData['banner_images'] = $bannerImages;
            $eventData['banner_image_urls'] = array_map(
                fn (string $path) => StorageRole::publicUrl($path),
                $bannerImages,
            );

            return $eventData;
        });

        return Inertia::render('events/Public', [
            'events' => $events,
            'mode' => $isPast ? 'past' : 'upcoming',
        ]);
    }

    public function show(Event $event, Request $request): Response
    {
        if ($event->status !== EventStatus::Published) {
            throw new NotFoundHttpException;
        }

        $event->load([
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
        ]);

        $eventData = $event->toArray();
        $eventData['seat_plans'] = SeatPlanResource::collection($event->seatPlans)->resolve();
        $eventData['taken_seats'] = $this->takenSeatsFor($event, $request);
        $bannerImages = array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== ''));
        $eventData['banner_images'] = $bannerImages;
        $eventData['banner_image_urls'] = array_map(
            fn (string $path) => StorageRole::publicUrl($path),
            $bannerImages,
        );

        if (isset($eventData['venue']['images'])) {
            $eventData['venue']['images'] = collect($eventData['venue']['images'])->map(function (array $image) {
                $image['url'] = StorageRole::publicUrl($image['path']);

                return $image;
            })->all();
        }

        if (isset($eventData['sponsors'])) {
            $eventData['sponsors'] = collect($eventData['sponsors'])->map(function (array $sponsor) {
                $sponsor['logo_url'] = $sponsor['logo'] ? StorageRole::publicUrl($sponsor['logo']) : null;

                return $sponsor;
            })->all();
        }

        $isPast = $event->end_date !== null && $event->end_date->isPast();

        if ($isPast) {
            $label = 'Past Event';
        } else {
            $nextUpcomingId = Event::published()->upcoming()->orderBy('start_date')->value('id');
            $label = $nextUpcomingId === $event->id ? 'Next Event' : 'Upcoming Event';
        }

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'nextEvent' => $eventData,
            'eventLabel' => $label,
            'latestNews' => [],
            'announcements' => [],
            'dismissedAnnouncementIds' => [],
            'openCompetitions' => Competition::query()
                ->where('event_id', $event->id)
                ->whereIn('status', [CompetitionStatus::Published, CompetitionStatus::RegistrationOpen])
                ->with(['game:id,name,slug', 'event:id,name'])
                ->withCount('teams')
                ->orderBy('registration_closes_at')
                ->get()
                ->map(fn (Competition $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                    'status' => $c->status->value,
                    'registration_open' => $c->status === CompetitionStatus::RegistrationOpen,
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
            'focusSeatId' => $this->resolveFocusSeatId($event, $request),
        ]);
    }

    /**
     * Snapshot of every seat assignment for this event, redacted per
     * {@see User::isSeatNameVisibleTo()} so the canvas can render occupant
     * names/avatars without leaking private profiles. Mirrors the payload
     * the homepage's WelcomeController produces.
     *
     * @return array<int, array<string, mixed>>
     */
    private function takenSeatsFor(Event $event, Request $request): array
    {
        $viewer = $request->user();

        return SeatAssignment::query()
            ->forEvent($event->id)
            ->with('user')
            ->get()
            ->map(function (SeatAssignment $assignment) use ($viewer, $event): array {
                $user = $assignment->user;
                $isVisible = $user->isSeatNameVisibleTo($viewer, $event);

                return [
                    'seat_plan_id' => $assignment->seat_plan_id,
                    'seat_id' => $assignment->seat_plan_seat_id,
                    'name' => $isVisible ? $user->name : null,
                    'username' => $isVisible ? $user->username : null,
                    'profile_emoji' => $isVisible ? $user->profile_emoji : null,
                    'short_bio' => $isVisible ? $user->short_bio : null,
                    'avatar_url' => $isVisible ? $user->avatarUrl() : null,
                    'banner_url' => $isVisible ? $user->bannerUrl() : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resolve `?focus_user=<id>` to the user's seat id on this event, but
     * only when the seat is visible to the requesting viewer. The link is
     * generated on the public profile via the "find on seat plan" quick
     * action; this method enforces the same visibility check on arrival so
     * a stale or hand-crafted URL can't leak a private seat.
     */
    private function resolveFocusSeatId(Event $event, Request $request): ?int
    {
        $focusUserId = $request->integer('focus_user');

        if ($focusUserId <= 0) {
            return null;
        }

        $focusUser = User::find($focusUserId);

        if ($focusUser === null || ! $focusUser->isSeatNameVisibleTo($request->user(), $event)) {
            return null;
        }

        $assignment = SeatAssignment::query()
            ->where('user_id', $focusUserId)
            ->whereHas('seatPlan', fn ($q) => $q->where('event_id', $event->id))
            ->first();

        return $assignment?->seat_plan_seat_id;
    }

    /**
     * @see docs/mil-std-498/SSS.md CAP-EVT-007
     * @see docs/mil-std-498/SRS.md EVT-F-012
     */
    public function ical(Event $event, BuildEventIcal $buildIcal): HttpResponse
    {
        if ($event->status !== EventStatus::Published) {
            throw new NotFoundHttpException;
        }

        $body = $buildIcal->execute($event);
        $filename = (Str::slug($event->name) ?: 'event').'.ics';

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
