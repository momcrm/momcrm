import { formatCents } from '@/lib/money';
import type { Service } from '@/types';

function billingPeriod(months: number | null): string {
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
 * A service's quantity and price per billing period, such as "2 × $49.95 per month".
 */
export function serviceDetail(service: Service): string {
    return service.unit_price_in_cents != null
        ? `${service.quantity} × ${formatCents(service.unit_price_in_cents)} per ${billingPeriod(service.billing_period_in_months)}`
        : `Quantity ${service.quantity}`;
}
