<?php

namespace App\Domain\DataLifecycle\DTOs;

use App\Domain\DataLifecycle\RetentionEvaluators\Contracts\RetentionEvaluator;
use Carbon\CarbonInterface;

/**
 * The output of a {@see RetentionEvaluator}.
 */
final readonly class RetentionVerdict
{
    public function __construct(
        public bool $holds,
        public ?CarbonInterface $until,
        public string $basis,
    ) {}

    public static function noHold(): self
    {
        return new self(false, null, 'No retention obligation; safe to scrub on deletion.');
    }

    public static function hold(CarbonInterface $until, string $basis): self
    {
        return new self(true, $until, $basis);
    }

    public function isExpired(?CarbonInterface $now = null): bool
    {
        if (! $this->holds) {
            return true;
        }

        if ($this->until === null) {
            return false;
        }

        return ($now ?? now())->greaterThanOrEqualTo($this->until);
    }
}
