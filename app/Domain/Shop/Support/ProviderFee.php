<?php

namespace App\Domain\Shop\Support;

/**
 * Snapshot of a provider-reported (or estimated) fee for a single order.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-021
 */
final readonly class ProviderFee
{
    public const SOURCE_PROVIDER = 'provider';

    public const SOURCE_ESTIMATED = 'estimated';

    public function __construct(
        public int $feeCents,
        public string $currency,
        public string $source,
    ) {}

    public static function provider(int $feeCents, string $currency): self
    {
        return new self($feeCents, strtolower($currency), self::SOURCE_PROVIDER);
    }

    public static function estimated(int $feeCents, string $currency): self
    {
        return new self($feeCents, strtolower($currency), self::SOURCE_ESTIMATED);
    }
}
