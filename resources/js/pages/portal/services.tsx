import { Head } from '@inertiajs/react';
import { Layers } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatusPill from '@/components/status-pill';
import { formatDay } from '@/lib/dates';
import { formatCents } from '@/lib/money';
import type { PortalService } from '@/types';

function period(months: number | null): string {
    if (months === null || months === 1) {
        return 'a month';
    }

    return months === 12 ? 'a year' : `every ${months} months`;
}

/**
 * The customer's services: what each is, where, and what it costs.
 */
export default function PortalServices({
    services,
}: {
    services: PortalService[];
}) {
    return (
        <>
            <Head title="Services" />

            <h1 className="text-xl font-semibold">Services</h1>

            {services.length === 0 ? (
                <EmptyState
                    icon={Layers}
                    title="No services"
                    description="The services you get from us show here."
                />
            ) : (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {services.map((service) => (
                        <li
                            key={service.id}
                            className="flex flex-wrap items-center gap-3 px-4 py-3 text-sm"
                        >
                            <span className="min-w-0 flex-1">
                                <span className="block font-medium">
                                    {service.name ??
                                        service.rate_name ??
                                        'Service'}
                                    {service.quantity !== null &&
                                        service.quantity > 1 &&
                                        ` × ${service.quantity}`}
                                </span>
                                <span className="block text-xs text-muted-foreground">
                                    {[
                                        service.location_name,
                                        service.billing_starts_on &&
                                            `Since ${formatDay(service.billing_starts_on)}`,
                                        service.ends_on &&
                                            `Ends ${formatDay(service.ends_on)}`,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </span>
                            </span>
                            {service.rental_in_cents !== null && (
                                <span className="text-right tabular-nums">
                                    {formatCents(
                                        service.rental_in_cents,
                                        service.currency,
                                    )}{' '}
                                    <span className="text-xs text-muted-foreground">
                                        {period(
                                            service.billing_period_in_months,
                                        )}
                                    </span>
                                </span>
                            )}
                            <StatusPill
                                tone={service.is_active ? 'success' : 'neutral'}
                            >
                                {service.status}
                            </StatusPill>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
