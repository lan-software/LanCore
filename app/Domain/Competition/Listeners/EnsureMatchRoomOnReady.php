<?php

namespace App\Domain\Competition\Listeners;

use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Chat\CompetitionRoomAutoJoin;
use App\Domain\Competition\Chat\MatchRoomPolicy;
use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Competition\Models\CompetitionTeamMember;

/**
 * Creates a per-match chat room when the match becomes orchestratable.
 * Auto-joins all current participants. Idempotent — `ChatService::ensureRoom`
 * is safe on re-emits.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-031
 */
class EnsureMatchRoomOnReady
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly CompetitionRoomAutoJoin $autoJoin,
    ) {}

    public function handle(MatchReadyForOrchestration $event): void
    {
        $room = $this->chat->ensureRoom(
            "competition:{$event->competition->id}:match:{$event->lanbracketsMatchId}",
            new MatchRoomPolicy,
            [
                'consumer_domain' => 'Competition',
                'title' => "{$event->competition->name} — match {$event->lanbracketsMatchId}",
            ],
        );

        $participantUserIds = $this->extractParticipantUserIds(
            $event->matchData,
            $event->competition->id,
        );

        if ($participantUserIds !== []) {
            $this->autoJoin->joinUsers($room, $participantUserIds);
        }
    }

    /**
     * Extracts user ids from the LanBrackets match payload. Participant slots
     * carry a `competition_participant_id`, which resolves through
     * `CompetitionTeam` (or solo) → team members.
     *
     * @param  array<string, mixed>  $matchData
     * @return array<int, int>
     */
    private function extractParticipantUserIds(array $matchData, string $competitionId): array
    {
        $participantIds = collect($matchData['participants'] ?? [])
            ->pluck('competition_participant_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();

        if ($participantIds === []) {
            return [];
        }

        return CompetitionTeamMember::query()
            ->whereHas('team', fn ($q) => $q->where('competition_id', $competitionId)
                ->whereIn('id', $participantIds))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
