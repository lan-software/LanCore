<?php

use App\Domain\Shop\Enums\Currency;
use App\Domain\Shop\Models\ShopSetting;
use App\Domain\Shop\Support\CurrencyResolver;

it('falls back to cashier.currency when shop_settings.currency is unset', function () {
    config()->set('cashier.currency', 'gbp');

    expect(CurrencyResolver::code())->toBe('gbp');
    expect(CurrencyResolver::upperCode())->toBe('GBP');
});

it('prefers shop_settings.currency over cashier.currency', function () {
    config()->set('cashier.currency', 'eur');
    ShopSetting::set('currency', 'usd');

    expect(CurrencyResolver::code())->toBe('usd');
    expect(CurrencyResolver::upperCode())->toBe('USD');
    expect(CurrencyResolver::symbol())->toBe('$');
});

it('falls through to eur when no setting and no cashier config is present', function () {
    config()->set('cashier.currency', null);

    expect(CurrencyResolver::code())->toBe('eur');
});

it('returns the matching Currency enum instance', function () {
    ShopSetting::set('currency', 'chf');

    expect(CurrencyResolver::currency())->toBe(Currency::CHF);
});

it('formats cents in the configured currency', function () {
    ShopSetting::set('currency', 'eur');
    expect(CurrencyResolver::formatCents(1234_56))->toBe('1.234,56 €');

    ShopSetting::set('currency', 'usd');
    expect(CurrencyResolver::formatCents(1234_56))->toBe('$ 1.234,56');
});

it('formats zero, small, and large amounts', function () {
    ShopSetting::set('currency', 'eur');

    expect(CurrencyResolver::formatCents(0))->toBe('0,00 €');
    expect(CurrencyResolver::formatCents(7))->toBe('0,07 €');
    expect(CurrencyResolver::formatCents(1_000_000_00))->toBe('1.000.000,00 €');
});

it('normalises a mixed-case stored currency to lowercase', function () {
    ShopSetting::set('currency', 'EUR');

    expect(CurrencyResolver::code())->toBe('eur');
    expect(CurrencyResolver::currency())->toBe(Currency::EUR);
});
