/**
 * A billing period in words, such as "month", "year" or "3 months".
 */
export function billingPeriodLabel(months: number | null): string {
    switch (months) {
        case 1:
            return 'month';
        case 12:
            return 'year';
        default:
            return `${months} months`;
    }
}

/**
 * How often something is billed, as a select option label: "Monthly",
 * "Yearly" or "Every 3 months".
 */
export function billingFrequencyLabel(months: number): string {
    switch (months) {
        case 1:
            return 'Monthly';
        case 12:
            return 'Yearly';
        default:
            return `Every ${months} months`;
    }
}
