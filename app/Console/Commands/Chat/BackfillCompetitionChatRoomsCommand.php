<?php

namespace App\Console\Commands\Chat;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Chat\CompetitionRoomAutoJoin;
use App\Domain\Competition\Chat\CompetitionRoomPolicy;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chat:backfill-competition-rooms')]
#[Description('Ensure a chat room exists for every Competition past Draft status, joining existing team members.')]
class BackfillCompetitionChatRoomsCommand extends Command
{
    public function handle(ChatService $chat, CompetitionRoomAutoJoin $autoJoin): int
    {
        $statuses = [
            CompetitionStatus::Published,
            CompetitionStatus::RegistrationOpen,
            CompetitionStatus::RegistrationClosed,
            CompetitionStatus::Running,
            CompetitionStatus::Finished,
            CompetitionStatus::Archived,
        ];

        $created = 0;
        $joined = 0;
        $touched = 0;

        Competition::query()
            ->whereIn('status', array_map(fn ($s) => $s->value, $statuses))
            ->orderBy('id')
            ->chunkById(100, function ($competitions) use ($chat, $autoJoin, &$created, &$joined, &$touched): void {
                foreach ($competitions as $competition) {
                    $key = "competition:{$competition->id}";
                    $existed = ChatRoom::query()->where('key', $key)->exists();

                    $room = $chat->ensureRoom(
                        $key,
                        new CompetitionRoomPolicy,
                        [
                            'consumer_domain' => 'Competition',
                            'title' => $competition->name,
                        ],
                    );

                    if (! $existed) {
                        $created++;
                    }
                    $touched++;

                    $memberUserIds = CompetitionTeamMember::query()
                        ->whereHas('team', fn ($q) => $q->where('competition_id', $competition->id))
                        ->whereNull('left_at')
                        ->pluck('user_id')
                        ->all();

                    $autoJoin->joinUsers($room, $memberUserIds);
                    $joined += count($memberUserIds);

                    if ($competition->status === CompetitionStatus::Finished) {
                        $chat->writeLock($room);
                    } elseif ($competition->status === CompetitionStatus::Archived) {
                        $chat->archive($room);
                    }
                }
            });

        $this->info("Touched {$touched} competitions; created {$created} new rooms; (re-)joined {$joined} team memberships.");

        return self::SUCCESS;
    }
}
