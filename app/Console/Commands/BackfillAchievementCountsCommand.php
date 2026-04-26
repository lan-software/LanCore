<?php

namespace App\Console\Commands;

use App\Domain\Achievements\Models\Achievement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes `achievements.earned_user_count` from the
 * `achievement_user` pivot. Run once after the rarity feature ships
 * (and any time pivot rows have been hand-edited around the action layer).
 *
 * @see docs/mil-std-498/SRS.md ACH-F-008
 */
class BackfillAchievementCountsCommand extends Command
{
    protected $signature = 'profiles:backfill-achievement-counts';

    protected $description = 'Recompute achievements.earned_user_count from the achievement_user pivot.';

    public function handle(): int
    {
        $rows = DB::table('achievement_user')
            ->select('achievement_id', DB::raw('COUNT(DISTINCT user_id) as cnt'))
            ->groupBy('achievement_id')
            ->pluck('cnt', 'achievement_id');

        $updated = 0;

        foreach (Achievement::query()->get() as $achievement) {
            $count = (int) ($rows[$achievement->id] ?? 0);
            $achievement->forceFill(['earned_user_count' => $count])->save();
            $updated++;
        }

        $this->info("Updated {$updated} achievement(s).");

        return self::SUCCESS;
    }
}
