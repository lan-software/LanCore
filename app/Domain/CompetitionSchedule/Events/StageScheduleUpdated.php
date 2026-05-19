<?php

namespace App\Domain\CompetitionSchedule\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a competition's stage schedule changes
 * (synced from LanBrackets, edited by organizer, or auto-fit).
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-006
 */
class StageScheduleUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?string $eventId,
        public readonly string $competitionId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->eventId === null) {
            return [];
        }

        return [
            new PrivateChannel("event.{$this->eventId}.competition-board"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stage-schedule.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'competition_id' => $this->competitionId,
        ];
    }
}
