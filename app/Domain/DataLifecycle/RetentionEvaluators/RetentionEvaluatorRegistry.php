<?php

namespace App\Domain\DataLifecycle\RetentionEvaluators;

use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\RetentionEvaluators\Contracts\RetentionEvaluator;

final class RetentionEvaluatorRegistry
{
    /** @var array<string, RetentionEvaluator> */
    private array $evaluators = [];

    public function register(RetentionEvaluator $evaluator): void
    {
        $this->evaluators[$evaluator->dataClass()->value] = $evaluator;
    }

    public function for(RetentionDataClass $class): ?RetentionEvaluator
    {
        return $this->evaluators[$class->value] ?? null;
    }

    /**
     * @return list<RetentionEvaluator>
     */
    public function all(): array
    {
        return array_values($this->evaluators);
    }
}
