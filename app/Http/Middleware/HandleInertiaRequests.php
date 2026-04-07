<?php

namespace App\Http\Middleware;

use App\Contracts\PermissionEnum;
use App\Domain\Event\Enums\Permission as EventPermission;
use App\Domain\Event\Models\Event;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Program\Enums\Permission as ProgramPermission;
use App\Domain\Seating\Enums\Permission as SeatingPermission;
use App\Domain\Ticketing\Enums\Permission as TicketingPermission;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
            'permissions' => $user ? array_map(
                fn (PermissionEnum $p) => $p->value,
                $user->allPermissions(),
            ) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebarFavorites' => $user ? ($user->sidebar_favorites ?? []) : [],
            'organization' => fn () => Cache::remember('inertia.organization', 3600, function () {
                $logoPath = OrganizationSetting::get('logo');

                return [
                    'logoUrl' => $logoPath ? Storage::url($logoPath) : null,
                    'name' => OrganizationSetting::get('name'),
                ];
            }),
            'eventContext' => fn () => $this->eventContext($request),
            'myEventContext' => fn () => $this->myEventContext($request),
            'vapidPublicKey' => config('services.vapid.public_key'),
            'integrationLinks' => fn () => IntegrationApp::query()
                ->where('is_active', true)
                ->whereNotNull('nav_url')
                ->whereNotNull('nav_label')
                ->get(['nav_url', 'nav_icon', 'nav_label'])
                ->map(fn (IntegrationApp $app) => [
                    'url' => $app->nav_url,
                    'icon' => $app->nav_icon,
                    'label' => $app->nav_label,
                ])
                ->values()
                ->all(),
            'pushSubscribed' => fn () => $user ? $user->pushSubscriptions()->exists() : false,
            'pushPromptDismissed' => fn () => (bool) $request->session()->get('push_prompt_dismissed', false),
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

        if (! $user || ! $user->hasAnyPermission(
            EventPermission::ManageEvents,
            ProgramPermission::ManagePrograms,
            TicketingPermission::ManageTicketing,
            SeatingPermission::ManageSeatPlans,
        )) {
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

    /**
     * @return array{selectedEventId: int|null, events: array<int, array{id: int, name: string}>}|null
     */
    private function myEventContext(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $events = Event::query()
            ->forUser($user)
            ->orderByDesc('start_date')
            ->get(['id', 'name'])
            ->map(fn (Event $e) => ['id' => $e->id, 'name' => $e->name])
            ->values()
            ->all();

        $selectedEventId = $request->session()->get('my_selected_event_id');

        if ($selectedEventId && ! collect($events)->contains('id', $selectedEventId)) {
            $request->session()->forget('my_selected_event_id');
            $selectedEventId = null;
        }

        return [
            'selectedEventId' => $selectedEventId,
            'events' => $events,
        ];
    }
}
