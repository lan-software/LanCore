<?php

namespace App\Domain\Competition\SignupRules\Contracts;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use App\Models\User;

/**
 * A single signup prerequisite that can be evaluated for a given user
 * against a given competition. Implementations are stateless and may be
 * constructed with a small config payload pulled from JSON storage.
 *
 * @see docs/mil-std-498/SRS.md COMP-F-014
 */
interface SignupRule
{
    /**
     * Stable identifier used both in JSON storage and in translation keys.
     * e.g. `has_steam_account_linked`, `has_ticket_of_type`.
     */
    public static function key(): string;

    public function isSatisfiedBy(User $user, Competition $competition): bool;

    /**
     * Translated, parameterised reason explaining why this rule is unmet.
     * Only called for rules that returned `false` from `isSatisfiedBy`.
     */
    public function reason(User $user, Competition $competition): SignupRuleReason;
}
