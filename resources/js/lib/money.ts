import type { RatePrice } from '@/types';

/** What a customer is billed in unless set otherwise. */
export const DEFAULT_CURRENCY = 'AUD';

const formatters = new Map<string, Intl.NumberFormat>();

function formatterFor(currency: string): Intl.NumberFormat {
    let formatter = formatters.get(currency);

    if (!formatter) {
        formatter = new Intl.NumberFormat('en-AU', {
            style: 'currency',
            currency,
        });
        formatters.set(currency, formatter);
    }

    return formatter;
}

/**
 * Formats an amount stored in cents in a currency, Australian dollars unless
 * told otherwise: 4995 → $49.95, or USD 49.95 in US dollars.
 */
export function formatCents(
    cents: number | null | undefined,
    currency: string = DEFAULT_CURRENCY,
): string {
    return cents == null ? '' : formatterFor(currency).format(cents / 100);
}

/** A rate's price in one currency, if it has one. */
export function priceIn(
    prices: RatePrice[],
    currency: string,
): RatePrice | undefined {
    return prices.find((price) => price.currency === currency);
}

/** Every price of a rate, e.g. "$95.00 · USD 62.00". */
export function formatPrices(prices: RatePrice[]): string {
    return prices
        .map((price) => formatCents(price.price_in_cents, price.currency))
        .join(' · ');
}
