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
 * Removes the user from sponsor manager pivots. The sponsor records remain.
 */
final class SponsoringAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::SponsoringRelation;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('sponsor_user')) {
            return AnonymizationResult::nothingToDo();
        }

        $deleted = DB::table('sponsor_user')->where('user_id', $user->getKey())->delete();

        return new AnonymizationResult(
            recordsScrubbed: $deleted,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
        );
    }
}
