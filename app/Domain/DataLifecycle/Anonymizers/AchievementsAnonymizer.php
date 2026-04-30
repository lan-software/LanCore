<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Achievement pivots keep the FK so leaderboards still credit the
 * (anonymized) user. PurgeNow drops the pivot rows.
 */
final class AchievementsAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::AchievementEarned;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('achievement_user')) {
            return AnonymizationResult::nothingToDo();
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $deleted = DB::table('achievement_user')->where('user_id', $user->getKey())->delete();

            return new AnonymizationResult(
                recordsScrubbed: $deleted,
                recordsKeptUnderRetention: 0,
                retentionUntil: null,
            );
        }

        $kept = DB::table('achievement_user')->where('user_id', $user->getKey())->count();

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: $kept,
            retentionUntil: null,
        );
    }
}
