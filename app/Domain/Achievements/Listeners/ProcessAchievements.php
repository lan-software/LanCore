<?php

namespace App\Domain\Achievements\Listeners;

use App\Domain\Achievements\Actions\GrantAchievement;
use App\Domain\Achievements\Models\AchievementEvent;
use App\Domain\Achievements\Support\GrantableEventRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued listener that grants achievements when a grantable event fires.
 *
 * Eligibility and user resolution are delegated to {@see GrantableEventRegistry}
 * so adding a new user-bound event needs no listener changes here.
 *
 * @see docs/mil-std-498/SRS.md ACH-F-006
 */
class ProcessAchievements implements ShouldQueue
{
    public function __construct(
        private readonly GrantAchievement $grantAchievement,
        private readonly GrantableEventRegistry $registry,
    ) {}

    public function handle(object $event): void
    {
        $user = $this->registry->userFor($event);

        if (! $user) {
            return;
        }

        $eventClass = get_class($event);

        $achievementEvents = AchievementEvent::query()
            ->where('event_class', $eventClass)
            ->with(['achievement' => fn ($q) => $q->where('is_active', true)])
            ->get();

        foreach ($achievementEvents as $achievementEvent) {
            if ($achievementEvent->achievement) {
                $this->grantAchievement->execute($user, $achievementEvent->achievement);
            }
        }
    }
}
