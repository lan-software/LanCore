<?php

namespace App\Domain\DataLifecycle\RetentionEvaluators\Contracts;

use App\Domain\DataLifecycle\DTOs\RetentionVerdict;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;

/**
 * Strategy for deciding whether a given user's data in a given data class
 * is currently under a retention hold (and if so, until when).
 *
 * @see docs/mil-std-498/IRS.md IF-DL-003
 */
interface RetentionEvaluator
{
    public function dataClass(): RetentionDataClass;

    public function evaluate(User $user): RetentionVerdict;
}
