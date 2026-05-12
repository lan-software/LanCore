<?php

namespace App\Domain\Shop\Support;

use App\Domain\Shop\Enums\PaymentMethod;

/**
 * Read-only description of a provider's fee schedule, derived from
 * `config/shop.php`. Used for display on the External APIs page and as
 * a fallback when computing estimated fees prior to webhook confirmation.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-021
 */
final readonly class ProviderFeeSchedule
{
    public function __construct(
        public string $providerKey,
        public int $percentageBasisPoints,
        public int $fixedCents,
        public string $currency,
        public ?string $note,
    ) {}

    public static function fromConfig(string $providerKey): self
    {
        $row = (array) config("shop.provider_fees.{$providerKey}", []);

        return new self(
            providerKey: $providerKey,
            percentageBasisPoints: (int) ($row['percentage'] ?? 0),
            fixedCents: (int) ($row['fixed_cents'] ?? 0),
            currency: strtolower((string) ($row['currency'] ?? 'eur')),
            note: isset($row['note']) ? (string) $row['note'] : null,
        );
    }

    public static function forMethod(PaymentMethod $method): self
    {
        return self::fromConfig(match ($method) {
            PaymentMethod::Stripe => 'stripe',
            PaymentMethod::PayPal => 'paypal',
            PaymentMethod::OnSite => 'on_site',
        });
    }

    /**
     * Apply the schedule to a gross amount (in minor currency units) and
     * return the estimated fee. Result is clamped to a non-negative integer.
     */
    public function estimateFor(int $grossCents): int
    {
        if ($grossCents <= 0) {
            return 0;
        }

        $percentual = (int) round(($grossCents * $this->percentageBasisPoints) / 10_000);

        return max(0, $percentual + $this->fixedCents);
    }

    /**
     * Human-readable form, e.g. "1.50% + €0.25". Currency formatting is left
     * to the frontend; this just exposes the structured values.
     *
     * @return array{percentage: float, fixed_cents: int, currency: string, note: string|null}
     */
    public function toDisplayArray(): array
    {
        return [
            'percentage' => $this->percentageBasisPoints / 100,
            'fixed_cents' => $this->fixedCents,
            'currency' => $this->currency,
            'note' => $this->note,
        ];
    }
}
