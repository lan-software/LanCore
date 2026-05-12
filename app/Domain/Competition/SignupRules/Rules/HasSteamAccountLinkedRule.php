<?php

namespace App\Domain\Competition\SignupRules\Rules;

use App\Domain\Auth\Steam\Enums\SteamLinkStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Contracts\SignupRule;
use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use App\Models\User;

class HasSteamAccountLinkedRule implements SignupRule
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = []) {}

    public static function key(): string
    {
        return 'has_steam_account_linked';
    }

    public function isSatisfiedBy(User $user, Competition $competition): bool
    {
        return SteamLinkStatus::for($user) !== SteamLinkStatus::NotLinked;
    }

    public function reason(User $user, Competition $competition): SignupRuleReason
    {
        return new SignupRuleReason(
            ruleKey: self::key(),
            messageKey: 'competitions.signupRules.reasons.has_steam_account_linked',
            actionUrl: '/settings/profile',
        );
    }
}
