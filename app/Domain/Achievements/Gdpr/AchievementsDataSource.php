<?php

namespace App\Domain\Achievements\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AchievementsDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'achievements';
    }

    public function label(): string
    {
        return 'Earned achievements';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        if (! Schema::hasTable('achievement_user')) {
            return new GdprDataSourceResult(['earned' => []]);
        }

        $rows = DB::table('achievement_user')
            ->join('achievements', 'achievement_user.achievement_id', '=', 'achievements.id')
            ->where('achievement_user.user_id', $user->id)
            ->get([
                'achievement_user.achievement_id',
                'achievements.name',
                'achievement_user.earned_at',
                'achievement_user.created_at',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();

        return new GdprDataSourceResult(['earned' => $rows]);
    }
}
