<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;
use App\Domain\Achievements\Notifications\AchievementEarnedNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-ACH-002, CAP-ACH-004, CAP-ACH-005
 * @see docs/mil-std-498/SRS.md ACH-F-002, ACH-F-003, ACH-F-004, ACH-F-008
 */
class GrantAchievement
{
    public function execute(User $user, Achievement $achievement): bool
    {
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return false;
        }

        DB::transaction(function () use ($user, $achievement): void {
            $user->achievements()->attach($achievement->id, [
                'earned_at' => now(),
            ]);

            $achievement->newQuery()
                ->whereKey($achievement->id)
                ->increment('earned_user_count');
        });

        $user->notify(new AchievementEarnedNotification($achievement));

        return true;
    }
}
