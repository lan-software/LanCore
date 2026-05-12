<?php

namespace App\Domain\Competition\SignupRules\Exceptions;

use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use RuntimeException;

/**
 * Thrown when a user attempts to sign up for a competition whose configured
 * rules are not satisfied. Carries the list of unmet reasons so callers can
 * surface them verbatim to the user.
 */
class SignupRulesNotMetException extends RuntimeException
{
    /**
     * @param  list<SignupRuleReason>  $reasons
     */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct('Signup rules are not met.');
    }

    /**
     * @return list<array{rule_key: string, message_key: string, params: array<string, scalar|null>, action_url: string|null}>
     */
    public function reasonsArray(): array
    {
        return array_map(fn (SignupRuleReason $r) => $r->toArray(), $this->reasons);
    }
}
