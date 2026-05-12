<?php

namespace App\Domain\Competition\SignupRules\Support;

use App\Domain\Competition\SignupRules\Contracts\SignupRule;
use App\Domain\Competition\SignupRules\Rules\HasAddonRule;
use App\Domain\Competition\SignupRules\Rules\HasSeatRule;
use App\Domain\Competition\SignupRules\Rules\HasSteamAccountLinkedRule;
use App\Domain\Competition\SignupRules\Rules\HasTicketOfTypeRule;
use InvalidArgumentException;

/**
 * Maps a stable rule key (stored in JSON config) to the concrete rule
 * class that knows how to evaluate it. Adding a new rule type is a one-line
 * change here plus the rule implementation.
 */
class SignupRuleRegistry
{
    /**
     * @var array<string, class-string<SignupRule>>
     */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            HasSteamAccountLinkedRule::key() => HasSteamAccountLinkedRule::class,
            HasTicketOfTypeRule::key() => HasTicketOfTypeRule::class,
            HasSeatRule::key() => HasSeatRule::class,
            HasAddonRule::key() => HasAddonRule::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function make(string $key, array $config = []): SignupRule
    {
        if (! isset($this->rules[$key])) {
            throw new InvalidArgumentException("Unknown signup rule key: {$key}");
        }

        $class = $this->rules[$key];

        return new $class($config);
    }

    public function has(string $key): bool
    {
        return isset($this->rules[$key]);
    }

    /**
     * @return list<string>
     */
    public function availableKeys(): array
    {
        return array_keys($this->rules);
    }
}
