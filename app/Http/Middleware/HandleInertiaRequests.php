<?php

namespace App\Http\Middleware;

use App\Domain\Event\Models\Event;
use App\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('roles');
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'roles' => $user->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name instanceof RoleName ? $role->name->value : $role->name,
                        'label' => $role->label,
                    ])->values()->all(),
                ]) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebarFavorites' => $user ? ($user->sidebar_favorites ?? []) : [],
            'eventContext' => fn () => $this->eventContext($request),
            'vapidPublicKey' => config('services.vapid.public_key'),
            'pushSubscribed' => fn () => $user ? $user->pushSubscriptions()->exists() : false,
            'unreadNotificationsCount' => fn () => $user ? $user->unreadNotifications()->whereNull('archived_at')->count() : 0,
            'recentNotifications' => fn () => $user
                ? $user->notifications()->whereNull('archived_at')->latest()->limit(5)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toISOString(),
                    'created_at' => $n->created_at->toISOString(),
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return array{selectedEventId: int|null, selectedEvent: array{id: int, name: string}|null, events: Collection}|null
     */
    private function eventContext(Request $request): ?array
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return null;
        }

        $selectedEventId = $request->session()->get('selected_event_id');
        $selectedEvent = null;

        if ($selectedEventId) {
            $selectedEvent = Event::find($selectedEventId, ['id', 'name'])?->only(['id', 'name']);

            if (! $selectedEvent) {
                $request->session()->forget('selected_event_id');
                $selectedEventId = null;
            }
        }

        return [
            'selectedEventId' => $selectedEventId,
            'selectedEvent' => $selectedEvent,
            'events' => Event::dropdownOptions(),
        ];
    }
}
