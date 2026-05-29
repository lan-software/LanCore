import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageProps: { shop?: { currency?: { code: string; symbol: string } } } =
    {};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: pageProps }),
}));

import {
    currencyCode,
    currencyFromCode,
    currencySymbol,
    formatCents,
    symbolForCode,
} from './money';

beforeEach(() => {
    delete pageProps.shop;
});

describe('symbolForCode', () => {
    it('maps the known ISO codes to their symbols', () => {
        expect(symbolForCode('EUR')).toBe('€');
        expect(symbolForCode('USD')).toBe('$');
        expect(symbolForCode('GBP')).toBe('£');
        expect(symbolForCode('CHF')).toBe('CHF');
    });

    it('is case-insensitive', () => {
        expect(symbolForCode('usd')).toBe('$');
    });

    it('defaults to € for null/undefined/empty', () => {
        expect(symbolForCode(null)).toBe('€');
        expect(symbolForCode(undefined)).toBe('€');
        expect(symbolForCode('')).toBe('€');
    });

    it('falls back to the upper-cased code for unknown currencies', () => {
        expect(symbolForCode('sek')).toBe('SEK');
    });
});

describe('currencyFromCode', () => {
    it('builds a currency object from a code', () => {
        expect(currencyFromCode('usd')).toEqual({ code: 'USD', symbol: '$' });
    });

    it('defaults to EUR when no code is given', () => {
        expect(currencyFromCode(null)).toEqual({ code: 'EUR', symbol: '€' });
    });
});

describe('formatCents', () => {
    it('formats EUR with a trailing symbol (de-DE grouping)', () => {
        expect(formatCents(123456)).toBe('1.234,56 €');
    });

    it('prefixes the symbol for USD and GBP', () => {
        expect(formatCents(500, { code: 'USD', symbol: '$' })).toBe('$ 5,00');
        expect(formatCents(1099, { code: 'GBP', symbol: '£' })).toBe('£ 10,99');
    });

    it('uses the explicit currency argument over the page currency', () => {
        pageProps.shop = { currency: { code: 'EUR', symbol: '€' } };
        expect(formatCents(100, { code: 'USD', symbol: '$' })).toBe('$ 1,00');
    });

    it('derives the currency from the shared page prop when omitted', () => {
        pageProps.shop = { currency: { code: 'USD', symbol: '$' } };
        expect(formatCents(250)).toBe('$ 2,50');
    });

    it('falls back to EUR when the page exposes no shop currency', () => {
        expect(formatCents(99)).toBe('0,99 €');
    });
});

describe('currencySymbol / currencyCode', () => {
    it('reads from the shared page prop', () => {
        pageProps.shop = { currency: { code: 'GBP', symbol: '£' } };
        expect(currencySymbol()).toBe('£');
        expect(currencyCode()).toBe('GBP');
    });

    it('defaults to EUR/€ without a shop prop', () => {
        expect(currencySymbol()).toBe('€');
        expect(currencyCode()).toBe('EUR');
    });
});
