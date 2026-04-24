<?php

namespace App\Domain\Shop\Enums;

/**
 * Configurable shop currency (single-value, admin-selectable).
 *
 * @see docs/mil-std-498/SRS.md SHP-F-018
 * @see docs/mil-std-498/DBDD.md shop_settings.currency, orders.currency
 */
enum Currency: string
{
    case EUR = 'eur';
    case USD = 'usd';
    case GBP = 'gbp';
    case CHF = 'chf';

    public function label(): string
    {
        return match ($this) {
            self::EUR => 'Euro (EUR)',
            self::USD => 'US Dollar (USD)',
            self::GBP => 'Pound Sterling (GBP)',
            self::CHF => 'Swiss Franc (CHF)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD => '$',
            self::GBP => '£',
            self::CHF => 'CHF',
        };
    }

    public function upperCode(): string
    {
        return strtoupper($this->value);
    }

    /**
     * Format an integer minor-unit amount (cents) using this currency's
     * symbol and the shop's canonical European format: thousands separator
     * "." and decimal separator ",", symbol trailing for EUR/CHF, leading
     * for USD/GBP.
     */
    public function formatCents(int $cents): string
    {
        $formatted = number_format($cents / 100, 2, ',', '.');

        return match ($this) {
            self::EUR => $formatted.' €',
            self::CHF => $formatted.' CHF',
            self::USD => '$ '.$formatted,
            self::GBP => '£ '.$formatted,
        };
    }
}
