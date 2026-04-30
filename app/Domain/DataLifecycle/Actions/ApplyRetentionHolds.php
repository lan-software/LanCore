<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\DTOs\RetentionVerdict;
use App\Domain\DataLifecycle\RetentionEvaluators\RetentionEvaluatorRegistry;
use App\Models\User;

/**
 * Aggregate verdict across every {@see RetentionEvaluator} for the user.
 * Returns the map of data class → verdict so callers can decide whether
 * a particular data class is purgeable yet.
 *
 * @see docs/mil-std-498/SRS.md DL-F-013
 */
class ApplyRetentionHolds
{
    public function __construct(private RetentionEvaluatorRegistry $registry) {}

    /**
     * @return array<string, RetentionVerdict> Indexed by data class string value.
     */
    public function execute(User $user): array
    {
        $verdicts = [];
        foreach ($this->registry->all() as $evaluator) {
            $verdicts[$evaluator->dataClass()->value] = $evaluator->evaluate($user);
        }

        return $verdicts;
    }
}
