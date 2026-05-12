<?php

namespace App\Domain\Presence\Listeners;

use App\Domain\Presence\Services\PresenceTracker;
use Illuminate\Auth\Events\Logout;

/**
 * Forces presence to Offline immediately on logout, instead of waiting for
 * the heartbeat TTL to expire (~30min). Without this, a logged-out user
 * would still surface as Active/Idle in the admin users list and chat
 * member panels for half an hour.
 *
 * @see docs/mil-std-498/SRS.md PRS-F-013
 */
class ClearPresenceOnLogout
{
    public function __construct(
        private readonly PresenceTracker $tracker,
    ) {}

    public function handle(Logout $event): void
    {
        if ($event->user !== null) {
            $this->tracker->forget($event->user);
        }
    }
}
