<?php

namespace App\Domain\Competition\SignupRules\Rules;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Contracts\SignupRule;
use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

class HasTicketOfTypeRule implements SignupRule
{
    /** @var list<int> */
    private readonly array $ticketTypeIds;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $ids = $config['ticket_type_ids'] ?? [];
        $this->ticketTypeIds = is_array($ids)
            ? array_values(array_filter(array_map(fn ($v) => is_numeric($v) ? (int) $v : null, $ids), fn ($v) => $v !== null))
            : [];
    }

    public static function key(): string
    {
        return 'has_ticket_of_type';
    }

    public function isSatisfiedBy(User $user, Competition $competition): bool
    {
        if ($this->ticketTypeIds === []) {
            return true;
        }

        $matches = function ($builder) use ($competition) {
            $builder->whereIn('ticket_type_id', $this->ticketTypeIds);

            if ($competition->event_id !== null) {
                $builder->where('event_id', $competition->event_id);
            }
        };

        return $user->ownedTickets()->where($matches)->exists()
            || $user->assignedTickets()->where($matches)->exists();
    }

    public function reason(User $user, Competition $competition): SignupRuleReason
    {
        $names = TicketType::query()
            ->whereIn('id', $this->ticketTypeIds)
            ->pluck('name')
            ->all();

        return new SignupRuleReason(
            ruleKey: self::key(),
            messageKey: 'competitions.signupRules.reasons.has_ticket_of_type',
            params: ['types' => implode(', ', $names) ?: '—'],
            actionUrl: '/shop',
        );
    }
}
