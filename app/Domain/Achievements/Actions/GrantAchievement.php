<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;
use App\Models\User;
use App\Notifications\AchievementEarnedNotification;

class GrantAchievement
{
    public function execute(User $user, Achievement $achievement): bool
    {
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return false;
        }

        $user->achievements()->attach($achievement->id, [
            'earned_at' => now(),
        ]);

        $user->notify(new AchievementEarnedNotification($achievement));

        return true;
    }
}
