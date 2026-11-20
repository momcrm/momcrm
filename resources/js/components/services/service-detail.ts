import { billingPeriodLabel } from '@/components/rates/billing-period';
import { formatCents } from '@/lib/money';
import type { Service, ServiceLifecycle } from '@/types';

export const serviceLifecycleLabels: Record<ServiceLifecycle, string> = {
    active: 'Active',
    inactive: 'Inactive',
    archived: 'Archived',
};

/**
 * What a service costs per billing period, such as "2 × $49.95 per month", or
 * just its quantity when it has no rate.
 */
export function serviceDetail(service: Service): string {
    const period = billingPeriodLabel(
        service.effective_billing_period_in_months,
    );

    return service.unit_price_in_cents != null
        ? `${service.quantity} × ${formatCents(service.unit_price_in_cents, service.currency)} per ${period}`
        : `Quantity ${service.quantity}`;
}

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

/**
 * A calendar day such as "2026-12-01" as "1 Dec 2026", or a dash.
 */
export function formatDay(day: string | null): string {
    return day
        ? dayFormatter.format(new Date(`${day.slice(0, 10)}T00:00:00`))
        : '—';
}
