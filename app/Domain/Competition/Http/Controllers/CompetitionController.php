<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Competition\Actions\CreateCompetition;
use App\Domain\Competition\Actions\DeleteCompetition;
use App\Domain\Competition\Actions\UpdateCompetition;
use App\Domain\Competition\Chat\CompetitionMemberAnnotator;
use App\Domain\Competition\Http\Requests\CompetitionIndexRequest;
use App\Domain\Competition\Http\Requests\StoreCompetitionRequest;
use App\Domain\Competition\Http\Requests\UpdateCompetitionRequest;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Rules\HasAddonRule;
use App\Domain\Competition\SignupRules\Rules\HasSeatRule;
use App\Domain\Competition\SignupRules\Rules\HasSteamAccountLinkedRule;
use App\Domain\Competition\SignupRules\Rules\HasTicketOfTypeRule;
use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Presence\Services\PresenceTracker;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-001, COMP-F-003, COMP-F-004
 */
class CompetitionController extends Controller
{
    public function __construct(
        private readonly CreateCompetition $createCompetition,
        private readonly UpdateCompetition $updateCompetition,
        private readonly DeleteCompetition $deleteCompetition,
        private readonly PolicyResolver $policyResolver,
        private readonly PresenceTracker $presenceTracker,
        private readonly CompetitionMemberAnnotator $competitionMemberAnnotator,
    ) {}

    public function index(CompetitionIndexRequest $request): Response
    {
        $this->authorize('create', Competition::class);

        $query = Competition::withCount('teams');

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('description', "%{$search}%");
            });
        }

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $competitions = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('competitions/Index', [
            'competitions' => $competitions,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page', 'event_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Competition::class);

        return Inertia::render('competitions/Create', [
            'games' => Game::where('is_active', true)->with('gameModes')->get(),
            'events' => Event::orderByDesc('start_date')->get(['id', 'name', 'start_date']),
            'selectedEventId' => session('selected_event_id'),
        ]);
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->authorize('create', Competition::class);

        $data = $request->safe()->except(['logo', 'banner', 'referee_ids']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('competitions/logos', StorageRole::publicDiskName());
        }
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('competitions/banners', StorageRole::publicDiskName());
        }

        $competition = $this->createCompetition->execute($data);

        if ($refereeIds = $request->validated('referee_ids')) {
            $competition->referees()->sync($refereeIds);
        }

        return redirect()->route('competitions.index');
    }

    public function edit(Competition $competition): Response
    {
        $this->authorize('update', $competition);

        $competition->load(['teams.captain', 'teams.activeMembers.user', 'game', 'gameMode', 'event', 'referees:id,name,email']);

        $chatRoom = ChatRoom::query()
            ->where('key', "competition:{$competition->id}")
            ->first();

        $chatPayload = $chatRoom !== null
            ? $this->buildChatPayload($chatRoom, request()->user())
            : null;

        $competitionData = $competition->toArray();
        $competitionData['logo_url'] = $competition->logo_url;
        $competitionData['banner_url'] = $competition->banner_url;
        $competitionData['referees'] = $competition->referees->map(fn (User $u): array => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ])->all();

        return Inertia::render('competitions/Edit', [
            'competition' => $competitionData,
            'games' => Game::where('is_active', true)->with('gameModes')->get(),
            'events' => Event::orderByDesc('start_date')->get(['id', 'name', 'start_date']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'lanbracketsEnabled' => config('lanbrackets.enabled'),
            'lanbracketsBaseUrl' => config('lanbrackets.base_url'),
            'chat' => $chatPayload,
            'signupRuleCatalog' => [
                'ruleTypes' => [
                    HasSteamAccountLinkedRule::key(),
                    HasTicketOfTypeRule::key(),
                    HasSeatRule::key(),
                    HasAddonRule::key(),
                ],
                'ticketTypes' => TicketType::query()
                    ->when($competition->event_id, fn ($q) => $q->where('event_id', $competition->event_id))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->all(),
                'addons' => Addon::query()
                    ->when($competition->event_id, fn ($q) => $q->where('event_id', $competition->event_id))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->all(),
                'gameSignupRules' => $competition->game?->signup_rules,
            ],
        ]);
    }

    /**
     * Mirror of `UserCompetitionController::serializeRoomPayload`. Kept inline
     * (rather than extracted) until a third call-site appears.
     *
     * @return array<string, mixed>|null
     */
    private function buildChatPayload(ChatRoom $room, $user): ?array
    {
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            return null;
        }

        $messages = $room->messages()
            ->withTrashed()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'room_id' => $m->room_id,
                'user_id' => $m->user_id,
                'user' => [
                    'id' => $m->user?->id,
                    'name' => $m->user?->name,
                    'username' => $m->user?->username,
                ],
                'body' => $m->body,
                'mentions' => $m->mentions_json ?? [],
                'deleted_at' => $m->deleted_at?->toIso8601String(),
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->all();

        $members = $this->competitionMemberAnnotator->annotate(
            $room->key,
            $room->memberships()
                ->with('user:id,name,username')
                ->get()
                ->map(fn ($m) => [
                    'user_id' => $m->user_id,
                    'name' => $m->user?->name,
                    'username' => $m->user?->username,
                    'role' => $m->role,
                    'muted_until' => $m->muted_until?->toIso8601String(),
                ])
                ->all(),
        );

        $presence = [];
        foreach ($this->presenceTracker->bulkStatusFor(collect($members)->pluck('user_id')->all()) as $userId => $status) {
            $presence[$userId] = $status->value;
        }

        return [
            'room' => [
                'id' => $room->id,
                'key' => $room->key,
                'title' => $room->title,
                'status' => $room->status->value,
                'can_post' => $policy->canPost($user, $room),
                'can_moderate' => $policy->canModerate($user, $room),
                'is_open' => $room->status->value === 'open',
                'is_archived' => $room->status->value === 'archived',
                'is_write_locked' => $room->status->value === 'write_locked',
            ],
            'messages' => $messages,
            'members' => $members,
            'memberPresence' => $presence,
        ];
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        $data = $request->safe()->except(['logo', 'banner', 'remove_logo', 'remove_banner', 'referee_ids']);

        if ($request->hasFile('logo')) {
            if ($competition->logo_path) {
                StorageRole::public()->delete($competition->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('competitions/logos', StorageRole::publicDiskName());
        } elseif ($request->boolean('remove_logo') && $competition->logo_path) {
            StorageRole::public()->delete($competition->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('banner')) {
            if ($competition->banner_path) {
                StorageRole::public()->delete($competition->banner_path);
            }
            $data['banner_path'] = $request->file('banner')->store('competitions/banners', StorageRole::publicDiskName());
        } elseif ($request->boolean('remove_banner') && $competition->banner_path) {
            StorageRole::public()->delete($competition->banner_path);
            $data['banner_path'] = null;
        }

        $this->updateCompetition->execute($competition, $data);

        if ($request->has('referee_ids')) {
            // RefereePicker emits a sentinel empty-string entry so this field
            // is always present; strip it before syncing the pivot.
            $refereeIds = array_values(array_filter(
                $request->validated('referee_ids', []),
                fn ($id) => is_string($id) && $id !== '',
            ));
            $competition->referees()->sync($refereeIds);
        }

        return back();
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        $this->authorize('delete', $competition);

        $this->deleteCompetition->execute($competition);

        return redirect()->route('competitions.index');
    }
}
