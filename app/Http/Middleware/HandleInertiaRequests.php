<?php

namespace App\Http\Middleware;

use App\Contracts\PermissionEnum;
use App\Domain\Announcement\Services\ActiveAnnouncementsProvider;
use App\Domain\Event\Enums\Permission as EventPermission;
use App\Domain\Event\Models\Event;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Program\Enums\Permission as ProgramPermission;
use App\Domain\Seating\Enums\Permission as SeatingPermission;
use App\Domain\Shop\Support\CurrencyResolver;
use App\Domain\Ticketing\Enums\Permission as TicketingPermission;
use App\Enums\RoleName;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Support\AppVersion;
use App\Support\StorageRole;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
            'locale' => fn () => app()->getLocale(),
            'availableLocales' => SetLocale::AVAILABLE,
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
                    'logoUrl' => $logoPath ? StorageRole::publicUrl($logoPath) : null,
                    'name' => OrganizationSetting::get('name'),
                    'hasImpressum' => (bool) OrganizationSetting::get('impressum_content'),
                    'hasPrivacy' => (bool) OrganizationSetting::get('privacy_content'),
                ];
            }),
            'appVersion' => fn () => AppVersion::summary(),
            'analytics' => fn () => config('services.plausible.enabled') && config('services.plausible.domain')
                ? [
                    'plausible' => [
                        'domain' => config('services.plausible.domain'),
                        'src' => config('services.plausible.src'),
                    ],
                ]
                : null,
            'cookiePreferences' => fn () => $user
                ? ($user->cookie_preferences ?? null)
                : null,
            'eventContext' => fn () => $this->eventContext($request),
            'myEventContext' => fn () => $this->myEventContext($request),
            'vapidPublicKey' => config('services.vapid.public_key'),
            'integrationLinks' => fn () => IntegrationApp::query()
                ->where('is_active', true)
                ->whereNotNull('nav_url')
                ->whereNotNull('nav_label')
                ->get(['slug', 'nav_url', 'nav_icon', 'nav_label'])
                ->filter(fn (IntegrationApp $app): bool => $this->canSeeIntegrationLink($app, $user))
                ->map(fn (IntegrationApp $app) => [
                    'url' => $app->nav_url,
                    'icon' => $app->nav_icon,
                    'label' => $app->nav_label,
                ])
                ->values()
                ->all(),
            'pushSubscribed' => fn () => $user ? $user->pushSubscriptions()->exists() : false,
            'pushPromptDismissed' => fn () => (bool) $request->session()->get('push_prompt_dismissed', false),
            'demoBanner' => fn () => config('app.demo') ? [
                'message' => config('app.demo_banner_message'),
                'mailpit_url' => config('app.demo_mailpit_url'),
            ] : null,
            'announcements' => fn () => app(ActiveAnnouncementsProvider::class)->forCurrentUser($request->user()),
            'shop' => fn () => [
                'currency' => [
                    'code' => CurrencyResolver::upperCode(),
                    'symbol' => CurrencyResolver::symbol(),
                ],
            ],
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
     * Per-integration visibility gate for the nav links shared with the frontend.
     *
     * LanEntrance controls physical event check-in/door flow and is only
     * meaningful for on-site staff, so its nav icon is hidden from guests and
     * regular attendees.
     */
    private function canSeeIntegrationLink(IntegrationApp $app, ?User $user): bool
    {
        if ($app->slug === 'lanentrance') {
            return $user !== null && (
                $user->hasRole(RoleName::Moderator)
                || $user->hasRole(RoleName::Admin)
                || $user->hasRole(RoleName::Superadmin)
            );
        }

        return true;
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
