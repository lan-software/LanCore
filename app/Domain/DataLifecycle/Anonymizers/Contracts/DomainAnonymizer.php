<?php

namespace App\Domain\DataLifecycle\Anonymizers\Contracts;

use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\DataLifecycle\Providers\DataLifecycleServiceProvider;
use App\Models\User;

/**
 * Per-domain anonymizer plugin. One implementation per data class managed by
 * {@see RetentionPolicy}. Implementations are
 * registered in {@see DataLifecycleServiceProvider}.
 *
 * @see docs/mil-std-498/IRS.md IF-DL-002
 */
interface DomainAnonymizer
{
    public function dataClass(): RetentionDataClass;

    /**
     * Apply the anonymization or purge to the given user's records in this domain.
     *
     * Implementations MUST be idempotent: running the same anonymizer twice on
     * the same user MUST yield the same end-state without raising.
     */
    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult;
}
