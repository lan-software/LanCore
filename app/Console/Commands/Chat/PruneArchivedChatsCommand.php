<?php

namespace App\Console\Commands\Chat;

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-026
 */
class PruneArchivedChatsCommand extends Command
{
    protected $signature = 'chat:prune-archived
        {--older-than= : Required. Delete only rooms archived more than this many days ago.}
        {--dry-run : Report what would be deleted without making changes.}
        {--competition= : Optional. Restrict to chat rooms whose backing competition has this id.}';

    protected $description = 'Delete chat rooms (and their messages, memberships, moderation actions) belonging to archived competitions.';

    public function handle(): int
    {
        $olderThan = $this->option('older-than');

        if ($olderThan === null || $olderThan === '') {
            $this->error('--older-than=<days> is required.');

            return self::FAILURE;
        }

        $days = (int) $olderThan;

        if ($days < 1) {
            $this->error('--older-than must be a positive integer (days).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $competitionId = $this->option('competition');

        $threshold = now()->subDays($days);

        $archivedCompetitionIds = Competition::query()
            ->where('status', CompetitionStatus::Archived)
            ->when($competitionId !== null, fn ($q) => $q->where('id', (int) $competitionId))
            ->pluck('id');

        if ($archivedCompetitionIds->isEmpty()) {
            $this->info('No archived competitions match the criteria — nothing to do.');

            return self::SUCCESS;
        }

        $consumerKeys = $archivedCompetitionIds
            ->flatMap(fn (string $id) => [
                "competition:{$id}",
            ])
            ->toArray();

        $rooms = ChatRoom::query()
            ->where('status', RoomStatus::Archived)
            ->where('archived_at', '<', $threshold)
            ->where(function ($q) use ($consumerKeys, $archivedCompetitionIds): void {
                $q->whereIn('key', $consumerKeys);
                foreach ($archivedCompetitionIds as $cid) {
                    $q->orWhere('key', 'like', "competition:{$cid}:match:%");
                }
            })
            ->get();

        if ($rooms->isEmpty()) {
            $this->info('No chat rooms match the criteria — nothing to do.');

            return self::SUCCESS;
        }

        $roomIds = $rooms->pluck('id')->all();
        $messageCount = ChatMessage::withTrashed()->whereIn('room_id', $roomIds)->count();
        $membershipCount = ChatRoomMembership::whereIn('room_id', $roomIds)->count();
        $moderationCount = ChatModerationAction::whereIn('room_id', $roomIds)->count();

        $this->line(sprintf(
            '%d rooms (%d messages, %d memberships, %d moderation actions)%s',
            $rooms->count(),
            $messageCount,
            $membershipCount,
            $moderationCount,
            $dryRun ? ' would be deleted.' : ' will be deleted.',
        ));

        if ($dryRun) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($roomIds): void {
            ChatMessage::withTrashed()->whereIn('room_id', $roomIds)->forceDelete();
            ChatModerationAction::whereIn('room_id', $roomIds)->delete();
            ChatRoomMembership::whereIn('room_id', $roomIds)->delete();
            ChatRoom::whereIn('id', $roomIds)->delete();
        });

        $this->info('Done.');

        return self::SUCCESS;
    }
}
