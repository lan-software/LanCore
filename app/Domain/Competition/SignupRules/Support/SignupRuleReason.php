<?php

namespace App\Domain\Competition\SignupRules\Support;

/**
 * Why a signup rule was not satisfied. Carries an i18n message key plus
 * an optional structured payload (so the frontend can render parameters
 * and an optional deep-link to fix the prerequisite).
 */
final readonly class SignupRuleReason
{
    /**
     * @param  array<string, scalar|null>  $params
     */
    public function __construct(
        public string $ruleKey,
        public string $messageKey,
        public array $params = [],
        public ?string $actionUrl = null,
    ) {}

    /**
     * @return array{rule_key: string, message_key: string, params: array<string, scalar|null>, action_url: string|null}
     */
    public function toArray(): array
    {
        return [
            'rule_key' => $this->ruleKey,
            'message_key' => $this->messageKey,
            'params' => $this->params,
            'action_url' => $this->actionUrl,
        ];
    }
}
