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
 * Audit retention is anchored to the latest audit row referencing the user
 * (as actor or auditable). Default retention aligns with accounting (10y).
 */
final class AuditEvaluator implements RetentionEvaluator
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::AuditAudit;
    }

    public function evaluate(User $user): RetentionVerdict
    {
        if (! Schema::hasTable('audits')) {
            return RetentionVerdict::noHold();
        }

        $latest = DB::table('audits')
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->getKey())
                    ->orWhere(function ($q) use ($user): void {
                        $q->where('auditable_type', User::class)
                            ->where('auditable_id', $user->getKey());
                    });
            })
            ->max('created_at');

        if ($latest === null) {
            return RetentionVerdict::noHold();
        }

        $policy = RetentionPolicy::query()
            ->where('data_class', RetentionDataClass::AuditAudit->value)
            ->first();

        $days = $policy?->retention_days ?? RetentionDataClass::AuditAudit->defaultRetentionDays();

        return RetentionVerdict::hold(
            CarbonImmutable::parse($latest)->addDays($days),
            $policy?->legal_basis ?? RetentionDataClass::AuditAudit->defaultLegalBasis(),
        );
    }
}
