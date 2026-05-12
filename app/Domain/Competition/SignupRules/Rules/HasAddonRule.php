<?php

namespace App\Domain\Competition\SignupRules\Rules;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Contracts\SignupRule;
use App\Domain\Competition\SignupRules\Support\SignupRuleReason;
use App\Domain\Ticketing\Models\Addon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HasAddonRule implements SignupRule
{
    /** @var list<int> */
    private readonly array $addonIds;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $ids = $config['addon_ids'] ?? [];
        $this->addonIds = is_array($ids)
            ? array_values(array_filter(array_map(fn ($v) => is_numeric($v) ? (int) $v : null, $ids), fn ($v) => $v !== null))
            : [];
    }

    public static function key(): string
    {
        return 'has_addon';
    }

    public function isSatisfiedBy(User $user, Competition $competition): bool
    {
        if ($this->addonIds === []) {
            return true;
        }

        $matchingTicketIds = DB::table('ticket_ticket_addon')
            ->whereIn('ticket_addon_id', $this->addonIds)
            ->pluck('ticket_id');

        if ($matchingTicketIds->isEmpty()) {
            return false;
        }

        $matches = function ($builder) use ($matchingTicketIds, $competition) {
            $builder->whereIn('id', $matchingTicketIds);

            if ($competition->event_id !== null) {
                $builder->where('event_id', $competition->event_id);
            }
        };

        return $user->ownedTickets()->where($matches)->exists()
            || $user->assignedTickets()->where($matches)->exists();
    }

    public function reason(User $user, Competition $competition): SignupRuleReason
    {
        $names = Addon::query()
            ->whereIn('id', $this->addonIds)
            ->pluck('name')
            ->all();

        return new SignupRuleReason(
            ruleKey: self::key(),
            messageKey: 'competitions.signupRules.reasons.has_addon',
            params: ['addons' => implode(', ', $names) ?: '—'],
            actionUrl: '/shop',
        );
    }
}
