<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Chat\CompetitionRoomAutoJoin;
use App\Domain\Competition\Chat\MatchRoomPolicy;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * User-facing match list for a competition. Fetches the live bracket from
 * LanBrackets (source of truth for matches) and merges per-match chat room
 * ids from the local DB so participants can jump into a match chat.
 *
 * @see docs/mil-std-498/SRS.md COMP-F-012, COMP-F-020
 */
class UserMatchController extends Controller
{
    public function __construct(
        private readonly LanBracketsClient $lanBrackets,
        private readonly ChatService $chat,
        private readonly CompetitionRoomAutoJoin $autoJoin,
    ) {}

    public function index(Request $request, Competition $competition): Response
    {
        $this->authorize('view', $competition);

        $user = $request->user();
        $competition->load(['teams.activeMembers']);

        $userTeam = $competition->teams
            ->first(fn ($team) => $team->activeMembers->contains('user_id', $user->id));

        $stages = [];
        $matchesByStage = [];

        if ($competition->isSyncedToLanBrackets()) {
            try {
                $stages = $this->lanBrackets->getStages($competition->lanbrackets_id);
            } catch (Throwable $e) {
                Log::warning('UserMatchController: failed to fetch stages', [
                    'competition_id' => $competition->id,
                    'message' => $e->getMessage(),
                ]);
            }

            foreach ($stages as $stage) {
                $stageId = isset($stage['id']) ? (string) $stage['id'] : '';
                if ($stageId === '') {
                    continue;
                }

                try {
                    $matchesByStage[$stageId] = $this->lanBrackets->getMatches(
                        $competition->lanbrackets_id,
                        $stageId,
                    );
                } catch (Throwable $e) {
                    Log::warning('UserMatchController: failed to fetch matches', [
                        'competition_id' => $competition->id,
                        'stage_id' => $stageId,
                        'message' => $e->getMessage(),
                    ]);
                    $matchesByStage[$stageId] = [];
                }
            }
        }

        $teamsByLocalId = $competition->teams->keyBy('id');

        $teamIdsByUserId = $this->teamIdsForUser($competition->id, $user->id);

        $chatRoomsByMatchId = ChatRoom::query()
            ->where('key', 'like', "competition:{$competition->id}:match:%")
            ->get(['id', 'key', 'status'])
            ->mapWithKeys(function (ChatRoom $room) {
                preg_match('/:match:([^:]+)$/', $room->key, $m);

                return [($m[1] ?? '') => $room];
            })
            ->filter(fn ($_, $k) => $k !== '');

        $serializedStages = [];
        foreach ($stages as $stage) {
            $stageId = isset($stage['id']) ? (string) $stage['id'] : '';
            $matches = $matchesByStage[$stageId] ?? [];

            $serializedStages[] = [
                'id' => $stageId,
                'name' => $stage['name'] ?? null,
                'stage_type' => $stage['stage_type'] ?? null,
                'status' => $stage['status'] ?? null,
                'matches' => array_map(
                    fn (array $match) => $this->serializeMatch(
                        $match,
                        $competition,
                        $teamsByLocalId,
                        $teamIdsByUserId,
                        $chatRoomsByMatchId,
                        $user,
                    ),
                    $matches,
                ),
            ];
        }

        return Inertia::render('competitions/user/Matches', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'userTeam' => $userTeam
                ? ['id' => $userTeam->id, 'name' => $userTeam->name]
                : null,
            'stages' => $serializedStages,
        ]);
    }

    /**
     * Lazy-create the match chat room when a participant clicks "Open chat" on
     * a match that has no room yet (orchestration may have skipped it).
     * Auto-joins the requesting user if they're a participant.
     */
    public function openChat(Request $request, Competition $competition, string $matchId): RedirectResponse
    {
        $this->authorize('view', $competition);

        $user = $request->user();
        $teamIds = $this->teamIdsForUser($competition->id, $user->id);

        if ($teamIds === []) {
            abort(403);
        }

        $room = $this->chat->ensureRoom(
            "competition:{$competition->id}:match:{$matchId}",
            new MatchRoomPolicy,
            [
                'consumer_domain' => 'Competition',
                'title' => "{$competition->name} — match {$matchId}",
            ],
        );

        $alreadyMember = ChatRoomMembership::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $alreadyMember) {
            $this->autoJoin->joinUsers($room, [$user->id]);
        }

        return redirect()->route('chat.rooms.show', ['room' => $room->id]);
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  Collection<int, CompetitionTeam>  $teamsByLocalId
     * @param  array<int, int>  $teamIdsByUserId
     * @param  Collection<int, ChatRoom>  $chatRoomsByMatchId
     * @return array<string, mixed>
     */
    private function serializeMatch(
        array $match,
        Competition $competition,
        Collection $teamsByLocalId,
        array $teamIdsByUserId,
        $chatRoomsByMatchId,
        $user,
    ): array {
        $matchId = (int) ($match['id'] ?? 0);
        $participants = collect($match['match_participants'] ?? $match['participants'] ?? []);

        $userOnMatch = $participants->contains(function ($p) use ($teamsByLocalId, $teamIdsByUserId) {
            if (($p['participant_type'] ?? null) !== 'team') {
                return false;
            }

            $externalRef = $p['external_reference_id'] ?? null;
            if (empty($externalRef)) {
                return false;
            }

            $team = $teamsByLocalId->get((int) $externalRef);

            return $team !== null && in_array($team->id, $teamIdsByUserId, true);
        });

        $room = $chatRoomsByMatchId->get($matchId);

        return [
            'id' => $matchId,
            'round_number' => $match['round_number'] ?? null,
            'sequence' => $match['sequence'] ?? null,
            'status' => $match['status'] ?? null,
            'participants' => $participants
                ->map(function ($p) use ($teamsByLocalId) {
                    $participantId = $p['competition_participant_id'] ?? null;

                    $team = null;
                    if (($p['participant_type'] ?? null) === 'team') {
                        $externalRef = $p['external_reference_id'] ?? null;
                        if (! empty($externalRef)) {
                            $team = $teamsByLocalId->get($externalRef);
                        }
                    }

                    return [
                        'participant_id' => $participantId,
                        'team_id' => $team?->id,
                        'team_name' => $team?->name ?? ($p['participant_name'] ?? null),
                        'score' => $p['score'] ?? null,
                        'result' => $p['result'] ?? null,
                    ];
                })
                ->all(),
            'user_is_participant' => $userOnMatch,
            'chat_room_id' => $room?->id,
            'chat_room_status' => $room?->status?->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function teamIdsForUser(string $competitionId, string $userId): array
    {
        return CompetitionTeamMember::query()
            ->whereHas('team', fn ($q) => $q->where('competition_id', $competitionId))
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->pluck('team_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }
}
