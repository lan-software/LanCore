<?php

namespace App\Domain\Shop\Support;

use App\Domain\Shop\Enums\Currency;
use App\Domain\Shop\Models\ShopSetting;

/**
 * Resolves the shop's configured single currency.
 *
 * Precedence: shop_settings.currency > config('cashier.currency') > 'eur'.
 * Call sites that render *historical* amounts (invoices, receipts) must
 * read Order::$currency instead, to avoid retroactive currency mutation.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-018
 * @see docs/mil-std-498/SDD.md Currency configuration
 */
class CurrencyResolver
{
    public static function currency(): Currency
    {
        $code = static::code();

        return Currency::tryFrom($code) ?? Currency::EUR;
    }

    public static function code(): string
    {
        $fallback = strtolower((string) (config('cashier.currency') ?: 'eur'));

        $stored = ShopSetting::get('currency', $fallback);

        return strtolower((string) ($stored ?: $fallback));
    }

    public static function upperCode(): string
    {
        return strtoupper(static::code());
    }

    public static function symbol(): string
    {
        return static::currency()->symbol();
    }

    public static function formatCents(int $cents): string
    {
        return static::currency()->formatCents($cents);
    }
}
