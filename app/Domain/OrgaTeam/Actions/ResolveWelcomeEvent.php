<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;

/**
 * Returns the next published event whose start_date is today or in the future.
 *
 * Lives in the OrgaTeam domain because it is currently the only consumer; lift
 * into the Event domain when a second feature needs the same selection.
 *
 * @see docs/mil-std-498/SRS.md OT-F-008
 */
class ResolveWelcomeEvent
{
    public function execute(): ?Event
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date')
            ->first();
    }
}
