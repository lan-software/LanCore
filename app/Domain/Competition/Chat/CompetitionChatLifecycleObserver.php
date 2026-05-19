<?php

namespace App\Domain\Competition\Chat;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;

/**
 * Drives the competition-wide chat room's lifecycle from the Competition
 * model's `status` column. Mapping (per chat-presence.md §Phase 3):
 *
 * - Draft → RegistrationOpen  : ensure the room exists (publish-equivalent).
 *                                Re-emits are safe because ensureRoom is idempotent.
 * - * → Finished              : write-lock the room. Existing readers retain access.
 * - * → Archived              : archive the room. All access dropped at the channel layer.
 *
 * Auto-join: members are added when team membership is created (handled by a
 * sibling observer on CompetitionTeamMember).
 *
 * @see docs/mil-std-498/SRS.md CHT-F-028
 */
class CompetitionChatLifecycleObserver
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly CompetitionRoomAutoJoin $autoJoin,
    ) {}

    public function updated(Competition $competition): void
    {
        if (! $competition->wasChanged('status')) {
            return;
        }

        $newStatus = $competition->status;

        // Ensure the room exists once the competition leaves Draft. Earlier the
        // observer only fired on `RegistrationOpen`, which meant any path that
        // jumped straight to a later status (seeders, factories, bulk admin
        // status edits) silently skipped room creation. ensureRoom is idempotent.
        if (in_array($newStatus, [
            CompetitionStatus::Published,
            CompetitionStatus::RegistrationOpen,
            CompetitionStatus::RegistrationClosed,
            CompetitionStatus::Running,
        ], true)) {
            $this->onPublish($competition);

            return;
        }

        if ($newStatus === CompetitionStatus::Finished) {
            $this->onFinalize($competition);

            return;
        }

        if ($newStatus === CompetitionStatus::Archived) {
            $this->onArchive($competition);
        }
    }

    private function onPublish(Competition $competition): void
    {
        $room = $this->chat->ensureRoom(
            "competition:{$competition->id}",
            new CompetitionRoomPolicy,
            [
                'consumer_domain' => 'Competition',
                'title' => $competition->name,
            ],
        );

        $existingMemberUserIds = CompetitionTeamMember::query()
            ->whereHas('team', fn ($q) => $q->where('competition_id', $competition->id))
            ->pluck('user_id')
            ->all();

        $this->autoJoin->joinUsers($room, $existingMemberUserIds);
    }

    private function onFinalize(Competition $competition): void
    {
        $room = $this->chat->ensureRoom(
            "competition:{$competition->id}",
            new CompetitionRoomPolicy,
            ['consumer_domain' => 'Competition', 'title' => $competition->name],
        );

        $this->chat->writeLock($room);
    }

    private function onArchive(Competition $competition): void
    {
        $room = $this->chat->ensureRoom(
            "competition:{$competition->id}",
            new CompetitionRoomPolicy,
            ['consumer_domain' => 'Competition', 'title' => $competition->name],
        );

        $this->chat->archive($room);

        // Cascade archive to all match rooms of this competition.
        ChatRoom::query()
            ->where('key', 'like', "competition:{$competition->id}:match:%")
            ->get()
            ->each(fn ($r) => $this->chat->archive($r));
    }
}
