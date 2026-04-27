<?php

namespace App\Http\Controllers;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Http\Resources\PublicProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @see docs/mil-std-498/SRS.md USR-F-023, USR-F-026
 * @see docs/mil-std-498/SSS.md CAP-USR-012, SEC-021
 */
class PublicProfileController extends Controller
{
    public function show(Request $request, string $username): Response
    {
        $user = $this->resolveByUsername($username);

        $visibility = $user->profile_visibility instanceof ProfileVisibility
            ? $user->profile_visibility
            : ProfileVisibility::LoggedIn;

        if (! $visibility->isVisibleTo($request->user(), $user)) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('u/Show', [
            'profile' => (new PublicProfileResource($user))->resolve($request),
            'achievements' => $this->achievementsPayload($user),
            'upcomingEvents' => $this->upcomingEventsPayload($user),
            'eventHistory' => $this->eventHistoryPayload($user),
            'isPreview' => false,
            'isOwner' => $request->user()?->getKey() === $user->getKey(),
        ]);
    }

    public function preview(Request $request): Response
    {
        $user = $request->user();

        if ($user === null || $user->username === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('u/Show', [
            'profile' => (new PublicProfileResource($user))->resolve($request),
            'achievements' => $this->achievementsPayload($user),
            'upcomingEvents' => $this->upcomingEventsPayload($user),
            'eventHistory' => $this->eventHistoryPayload($user),
            'isPreview' => true,
            'isOwner' => true,
        ]);
    }

    private function resolveByUsername(string $username): User
    {
        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->first();

        if ($user === null) {
            throw new NotFoundHttpException;
        }

        return $user;
    }

    /**
     * Earned achievements with global rarity. The denominator is the
     * cached total user count (60 s TTL) so rarity reads are O(1) at
     * page render time.
     *
     * @return array<int, array<string, mixed>>
     */
    private function achievementsPayload(User $user): array
    {
        $totalUsers = Cache::remember('users.count', 60, fn (): int => User::query()->count());
        $denominator = max(1, $totalUsers);

        return $user->achievements()
            ->orderByPivot('earned_at', 'desc')
            ->get()
            ->map(function ($achievement) use ($denominator): array {
                $count = (int) ($achievement->earned_user_count ?? 0);
                $percentage = round(($count / $denominator) * 100, 1);

                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'color' => $achievement->color ?? null,
                    'earned_at' => optional($achievement->pivot->earned_at)?->toIso8601String(),
                    'earned_user_count' => $count,
                    'earned_percentage' => $percentage,
                ];
            })
            ->all();
    }

    /**
     * Past events the user participated in (ticket owner/manager/assignee,
     * order, or competition team membership). Capped at 100 entries; the
     * link is only emitted for Published events so visitors don't land
     * on 404s for drafted/archived events.
     *
     * @return array<int, array<string, mixed>>
     */
    private function eventHistoryPayload(User $user): array
    {
        return Event::query()
            ->forUser($user)
            ->past()
            ->with('venue:id,name')
            ->orderByDesc('start_date')
            ->limit(100)
            ->get()
            ->map(fn (Event $event): array => $this->eventListItem($event))
            ->all();
    }

    /**
     * Upcoming events the user is registered for. Restricted to Published
     * events so visitors don't see drafted/internal events the profile
     * owner happens to hold a ticket for. Sorted earliest-first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingEventsPayload(User $user): array
    {
        return Event::query()
            ->forUser($user)
            ->upcoming()
            ->published()
            ->with('venue:id,name')
            ->orderBy('start_date')
            ->limit(50)
            ->get()
            ->map(fn (Event $event): array => $this->eventListItem($event))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventListItem(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'start_date' => $event->start_date?->toIso8601String(),
            'end_date' => $event->end_date?->toIso8601String(),
            'venue_name' => $event->venue?->name,
            'public_url' => $event->status === EventStatus::Published
                ? route('events.public.show', ['event' => $event->id])
                : null,
        ];
    }
}
