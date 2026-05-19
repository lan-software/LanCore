<?php

namespace App\Console\Commands\Presence;

use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Presence\Services\PresenceTracker;
use Illuminate\Console\Command;

/**
 * Detects Active → Idle and Idle → Offline transitions that nothing else
 * triggers (since heartbeat decay is passive — the user just stops sending
 * page requests). Runs every minute via the scheduler; iterates each user
 * with at least one chat room membership and calls
 * `PresenceTracker::broadcastChange()` with the currently-derived status.
 * The tracker dedupes against `presence:last_broadcast:user:{id}` so the
 * broadcast only happens on actual transitions.
 *
 * @see docs/mil-std-498/SRS.md PRS-F-015
 */
class SweepPresenceTransitionsCommand extends Command
{
    protected $signature = 'presence:sweep';

    protected $description = 'Broadcast LanCore presence transitions (Active → Idle → Offline) for chat-room members.';

    public function handle(PresenceTracker $tracker): int
    {
        $userIds = ChatRoomMembership::query()
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($userIds === []) {
            return self::SUCCESS;
        }

        $statuses = $tracker->bulkStatusFor($userIds);

        foreach ($statuses as $userId => $status) {
            $tracker->broadcastChange((string) $userId, $status);
        }

        return self::SUCCESS;
    }
}
