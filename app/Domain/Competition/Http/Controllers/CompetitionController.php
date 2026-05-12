<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Competition\Actions\CreateCompetition;
use App\Domain\Competition\Chat\CompetitionMemberAnnotator;
use App\Domain\Competition\Actions\DeleteCompetition;
use App\Domain\Competition\Actions\UpdateCompetition;
use App\Domain\Competition\Http\Requests\CompetitionIndexRequest;
use App\Domain\Competition\Http\Requests\StoreCompetitionRequest;
use App\Domain\Competition\Http\Requests\UpdateCompetitionRequest;
use App\Domain\Competition\Models\Competition;
use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Presence\Services\PresenceTracker;
use App\Http\Controllers\Controller;
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

        $this->createCompetition->execute($request->validated());

        return redirect()->route('competitions.index');
    }

    public function edit(Competition $competition): Response
    {
        $this->authorize('update', $competition);

        $competition->load(['teams.captain', 'teams.activeMembers.user', 'game', 'gameMode', 'event']);

        $chatRoom = ChatRoom::query()
            ->where('key', "competition:{$competition->id}")
            ->first();

        $chatPayload = $chatRoom !== null
            ? $this->buildChatPayload($chatRoom, request()->user())
            : null;

        return Inertia::render('competitions/Edit', [
            'competition' => $competition,
            'games' => Game::where('is_active', true)->with('gameModes')->get(),
            'events' => Event::orderByDesc('start_date')->get(['id', 'name', 'start_date']),
            'lanbracketsEnabled' => config('lanbrackets.enabled'),
            'lanbracketsBaseUrl' => config('lanbrackets.base_url'),
            'chat' => $chatPayload,
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

        $this->updateCompetition->execute($competition, $request->validated());

        return back();
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        $this->authorize('delete', $competition);

        $this->deleteCompetition->execute($competition);

        return redirect()->route('competitions.index');
    }
}
