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
 * Hard-deletes all of the user's web sessions. Sessions carry IP and
 * user-agent PII and have no retention obligation; they're terminated
 * immediately so the deletion request also forces a logout everywhere.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004
 */
final class SessionsAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::SessionsSession;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('sessions')) {
            return AnonymizationResult::nothingToDo();
        }

        $deleted = DB::table('sessions')->where('user_id', $user->getKey())->delete();

        return new AnonymizationResult(
            recordsScrubbed: $deleted,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
        );
    }
}
