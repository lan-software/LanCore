<?php

namespace App\Domain\Competition\SignupRules\Support;

final readonly class SignupRuleEvaluationResult
{
    /**
     * @param  list<SignupRuleReason>  $unmetReasons
     */
    public function __construct(
        public bool $satisfied,
        public array $unmetReasons,
    ) {}

    /**
     * @return list<array{rule_key: string, message_key: string, params: array<string, scalar|null>, action_url: string|null}>
     */
    public function reasonsArray(): array
    {
        return array_map(fn (SignupRuleReason $r) => $r->toArray(), $this->unmetReasons);
    }
}
