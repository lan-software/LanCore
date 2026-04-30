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
 * Competition team membership. Pivots stay on Anonymize so historical
 * standings remain accurate; PurgeNow drops them.
 */
final class CompetitionAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::CompetitionParticipation;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('competition_team_members')) {
            return AnonymizationResult::nothingToDo();
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $deleted = DB::table('competition_team_members')->where('user_id', $user->getKey())->delete();

            return new AnonymizationResult(
                recordsScrubbed: $deleted,
                recordsKeptUnderRetention: 0,
                retentionUntil: null,
            );
        }

        $kept = DB::table('competition_team_members')->where('user_id', $user->getKey())->count();

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: $kept,
            retentionUntil: null,
        );
    }
}
