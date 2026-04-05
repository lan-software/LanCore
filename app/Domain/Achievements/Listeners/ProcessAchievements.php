<?php

namespace App\Domain\Achievements\Listeners;

use App\Domain\Achievements\Actions\GrantAchievement;
use App\Domain\Achievements\Models\AchievementEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessAchievements implements ShouldQueue
{
    public function __construct(private readonly GrantAchievement $grantAchievement) {}

    public function handle(object $event): void
    {
        $user = $this->resolveUser($event);

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

    private function resolveUser(object $event): ?User
    {
        if ($event instanceof Registered) {
            return $event->user instanceof User ? $event->user : null;
        }

        if (property_exists($event, 'user') && $event->user instanceof User) {
            return $event->user;
        }

        if (method_exists($event, 'user')) {
            $user = $event->user();

            return $user instanceof User ? $user : null;
        }

        return null;
    }
}
