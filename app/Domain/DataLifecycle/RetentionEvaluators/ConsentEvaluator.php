<?php

namespace App\Domain\DataLifecycle\RetentionEvaluators;

use App\Domain\DataLifecycle\DTOs\RetentionVerdict;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\DataLifecycle\RetentionEvaluators\Contracts\RetentionEvaluator;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consent acceptance / withdrawal retention. Anchored to the latest
 * acceptance or withdrawal timestamp.
 */
final class ConsentEvaluator implements RetentionEvaluator
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::PolicyAcceptance;
    }

    public function evaluate(User $user): RetentionVerdict
    {
        if (! Schema::hasTable('policy_acceptances')) {
            return RetentionVerdict::noHold();
        }

        $latest = DB::table('policy_acceptances')
            ->where('user_id', $user->getKey())
            ->max('updated_at');

        if ($latest === null) {
            return RetentionVerdict::noHold();
        }

        $policy = RetentionPolicy::query()
            ->where('data_class', RetentionDataClass::PolicyAcceptance->value)
            ->first();

        $days = $policy?->retention_days ?? RetentionDataClass::PolicyAcceptance->defaultRetentionDays();

        return RetentionVerdict::hold(
            CarbonImmutable::parse($latest)->addDays($days),
            $policy?->legal_basis ?? RetentionDataClass::PolicyAcceptance->defaultLegalBasis(),
        );
    }
}
