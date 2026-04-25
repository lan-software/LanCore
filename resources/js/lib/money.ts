import { usePage } from '@inertiajs/vue3';

export interface ShopCurrency {
    code: string;
    symbol: string;
}

const SYMBOLS: Record<string, string> = {
    EUR: '€',
    USD: '$',
    GBP: '£',
    CHF: 'CHF',
};

export function symbolForCode(code: string | null | undefined): string {
    if (!code) {
        return '€';
    }

    return SYMBOLS[code.toUpperCase()] ?? code.toUpperCase();
}

export function currencyFromCode(
    code: string | null | undefined,
): ShopCurrency {
    const upper = (code ?? 'EUR').toUpperCase();

    return { code: upper, symbol: symbolForCode(upper) };
}

function currencyFromPage(): ShopCurrency {
    const page = usePage();
    const shop = page.props.shop as { currency?: ShopCurrency } | undefined;

    return shop?.currency ?? { code: 'EUR', symbol: '€' };
}

/**
 * Format an integer minor-unit amount (cents) using the shop's configured
 * currency. Pass `currency` explicitly when formatting a historical amount
 * (e.g. order snapshot); omit it to use the current shop currency shared
 * via Inertia.
 */
export function formatCents(cents: number, currency?: ShopCurrency): string {
    const { code, symbol } = currency ?? currencyFromPage();
    const amount = (cents / 100).toLocaleString('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    if (code === 'USD' || code === 'GBP') {
        return `${symbol} ${amount}`;
    }

    return `${amount} ${symbol}`;
}

export function currencySymbol(): string {
    return currencyFromPage().symbol;
}

export function currencyCode(): string {
    return currencyFromPage().code;
}
