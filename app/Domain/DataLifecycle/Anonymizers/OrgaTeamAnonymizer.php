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
 * Removes the user from any organizer team membership pivots so they no
 * longer appear in team rosters. The team itself is unaffected.
 */
final class OrgaTeamAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::OrgaTeamMembership;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('orga_team_user')) {
            return AnonymizationResult::nothingToDo();
        }

        $deleted = DB::table('orga_team_user')->where('user_id', $user->getKey())->delete();

        return new AnonymizationResult(
            recordsScrubbed: $deleted,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
        );
    }
}
