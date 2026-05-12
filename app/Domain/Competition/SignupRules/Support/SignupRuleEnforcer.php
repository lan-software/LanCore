<?php

namespace App\Domain\Competition\SignupRules\Support;

use App\Domain\Competition\Models\Competition;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Bridge between the rule evaluator and the action layer. Evaluates the
 * competition's effective rule tree for the given user; if any rule is
 * unmet, throws a `ValidationException` whose `signup_rules` error bag
 * carries the translated reason strings. Callers get standard Inertia
 * back-redirect + 422 behaviour without per-action plumbing.
 */
class SignupRuleEnforcer
{
    public function __construct(private readonly SignupRuleEvaluator $evaluator) {}

    public function enforce(User $user, Competition $competition): void
    {
        $result = $this->evaluator->evaluate(
            $competition->effectiveSignupRules(),
            $user,
            $competition,
        );

        if ($result->satisfied) {
            return;
        }

        $messages = [];
        foreach ($result->unmetReasons as $reason) {
            $messages[] = __($reason->messageKey, $reason->params);
        }

        throw ValidationException::withMessages([
            'signup_rules' => $messages,
        ]);
    }

    public function evaluate(User $user, Competition $competition): SignupRuleEvaluationResult
    {
        return $this->evaluator->evaluate(
            $competition->effectiveSignupRules(),
            $user,
            $competition,
        );
    }
}
