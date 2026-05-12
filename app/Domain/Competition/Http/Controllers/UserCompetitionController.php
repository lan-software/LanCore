<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Competition\Chat\CompetitionMemberAnnotator;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Support\SignupRuleEnforcer;
use App\Domain\Presence\Services\PresenceTracker;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-012
 */
class UserCompetitionController extends Controller
{
    public function __construct(
        private readonly PolicyResolver $policyResolver,
        private readonly PresenceTracker $presenceTracker,
        private readonly CompetitionMemberAnnotator $competitionMemberAnnotator,
        private readonly SignupRuleEnforcer $signupRuleEnforcer,
    ) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $selectedEventId = $request->session()->get('my_selected_event_id');

        $competitions = Competition::query()
            ->whereHas('teams.activeMembers', fn ($q) => $q->where('user_id', $userId))
            ->when($selectedEventId, fn ($q) => $q->where('event_id', $selectedEventId))
            ->with(['game', 'event'])
            ->withCount('teams')
            ->orderByDesc('created_at')
            ->paginate(12);

        return Inertia::render('competitions/user/Index', [
            'competitions' => $competitions,
        ]);
    }

    public function show(Request $request, Competition $competition): Response
    {
        $this->authorize('view', $competition);

        $userId = $request->user()->id;

        $competition->load([
            'game',
            'gameMode',
            'event',
            'teams' => fn ($q) => $q->withCount('activeMembers'),
            'teams.captain',
            'teams.activeMembers.user',
        ]);

        $userTeam = $competition->teams
            ->first(fn ($team) => $team->activeMembers->contains('user_id', $userId));

        $competitionChatRoom = ChatRoom::query()
            ->where('key', "competition:{$competition->id}")
            ->first();

        $chatPayload = null;
        if ($competitionChatRoom !== null && $userTeam !== null) {
            $chatPayload = $this->serializeRoomPayload($competitionChatRoom, $request->user());
        }

        $signupGate = $this->buildSignupGate($competition, $request->user());

        return Inertia::render('competitions/user/Show', [
            'competition' => $competition,
            'userTeam' => $userTeam,
            'bracketUrl' => $competition->lanBracketsViewUrl(),
            'chat' => $chatPayload,
            'signupGate' => $signupGate,
        ]);
    }

    /**
     * Evaluate the competition's signup rules for the authenticated user and
     * package the result for the frontend. Returns `null` when the user is
     * already on a team (gate doesn't apply once joined).
     *
     * @return array{allowed: bool, unmetReasons: list<array{rule_key: string, message_key: string, params: array<string, scalar|null>, action_url: string|null}>}
     */
    private function buildSignupGate(Competition $competition, $user): array
    {
        $result = $this->signupRuleEnforcer->evaluate($user, $competition);

        return [
            'allowed' => $result->satisfied,
            'unmetReasons' => $result->reasonsArray(),
        ];
    }

    /**
     * Returns the same payload shape as `ChatRoomController@show` so the
     * embedded `<ChatRoom>` Vue component can mount without an extra round-trip.
     * Returns null when the user isn't allowed to view the room (controller-level
     * authorization is handled before this is called).
     *
     * @return array<string, mixed>|null
     */
    private function serializeRoomPayload(ChatRoom $room, $user): ?array
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
            ->map(fn ($message) => [
                'id' => $message->id,
                'room_id' => $message->room_id,
                'user_id' => $message->user_id,
                'user' => [
                    'id' => $message->user?->id,
                    'name' => $message->user?->name,
                    'username' => $message->user?->username,
                ],
                'body' => $message->body,
                'mentions' => $message->mentions_json ?? [],
                'deleted_at' => $message->deleted_at?->toIso8601String(),
                'created_at' => $message->created_at?->toIso8601String(),
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
}
