<?php

namespace App\Domain\Competition\SignupRules\Rules;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Contracts\SignupRule;
use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use App\Domain\Seating\Models\SeatAssignment;
use App\Models\User;

class HasSeatRule implements SignupRule
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = []) {}

    public static function key(): string
    {
        return 'has_seat';
    }

    public function isSatisfiedBy(User $user, Competition $competition): bool
    {
        $query = SeatAssignment::query()->where('user_id', $user->id);

        if ($competition->event_id !== null) {
            $query->forEvent($competition->event_id);
        }

        return $query->exists();
    }

    public function reason(User $user, Competition $competition): SignupRuleReason
    {
        return new SignupRuleReason(
            ruleKey: self::key(),
            messageKey: 'competitions.signupRules.reasons.has_seat',
            actionUrl: '/portal/seating',
        );
    }
}
