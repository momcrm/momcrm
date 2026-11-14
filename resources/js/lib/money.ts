const audFormatter = new Intl.NumberFormat('en-AU', {
    style: 'currency',
    currency: 'AUD',
});

/**
 * Formats an amount stored in cents as Australian dollars, e.g. 4995 → $49.95.
 */
export function formatCents(cents: number | null | undefined): string {
    return cents == null ? '' : audFormatter.format(cents / 100);
}
