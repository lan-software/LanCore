<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scrubs IP / user-agent fields from {@see PolicyAcceptance}
 * rows. Acceptance and withdrawal records themselves are kept under retention as
 * proof of consent (Art. 7(1) GDPR).
 */
final class PolicyAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::PolicyAcceptance;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('policy_acceptances')) {
            return AnonymizationResult::nothingToDo();
        }

        $piiPayload = $this->piiPayload();

        if ($mode === AnonymizationMode::PurgeNow) {
            $policy = $this->maybePolicy();
            if ($policy === null || $policy->can_be_force_deleted) {
                $deleted = DB::table('policy_acceptances')->where('user_id', $user->getKey())->delete();

                return new AnonymizationResult(
                    recordsScrubbed: $deleted,
                    recordsKeptUnderRetention: 0,
                    retentionUntil: null,
                );
            }
        }

        $scrubbed = DB::table('policy_acceptances')
            ->where('user_id', $user->getKey())
            ->update($piiPayload);

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: $scrubbed,
            retentionUntil: $this->retentionUntil(),
            summary: ['pii_columns_scrubbed' => array_keys($piiPayload)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function piiPayload(): array
    {
        $payload = [];

        foreach (['accepted_ip', 'accepted_user_agent', 'withdrawn_ip', 'withdrawn_user_agent'] as $col) {
            if (Schema::hasColumn('policy_acceptances', $col)) {
                $payload[$col] = null;
            }
        }

        return $payload;
    }

    private function maybePolicy(): ?RetentionPolicy
    {
        return RetentionPolicy::query()->where('data_class', $this->dataClass()->value)->first();
    }

    private function retentionUntil(): ?CarbonInterface
    {
        $policy = $this->maybePolicy();

        return $policy === null ? null : now()->addDays($policy->retention_days);
    }
}
