import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCustomFields } from '@/components/custom-fields/custom-field-input';
import {
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import { billingPeriodLabel } from '@/components/rates/billing-period';
import { formatDay } from '@/components/services/service-detail';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { formatCents } from '@/lib/money';
import { show as showCustomer } from '@/routes/customers';
import type { ServiceWorkspace } from '@/types';

type Detail = { label: string; value: ReactNode };

function Panel({ title, details }: { title: string; details: Detail[] }) {
    return (
        <section className="rounded-xl border bg-card p-4 shadow-xs">
            <h2 className="mb-3 text-sm font-medium">{title}</h2>
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                {details.map((detail) => (
                    <div key={detail.label} className="min-w-0">
                        <dt className="text-xs text-muted-foreground">
                            {detail.label}
                        </dt>
                        <dd className="truncate">{detail.value || '—'}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

export default function ServiceOverview({
    workspace,
    dates,
}: {
    workspace: ServiceWorkspace;
    dates: { created_at: string | null; updated_at: string | null };
}) {
    const { service, customer, location, contact } = workspace;
    useServiceBreadcrumbs(workspace);
    const customFields = useCustomFields('service');
    const period = billingPeriodLabel(
        service.effective_billing_period_in_months,
    );

    const billing: Detail[] = [
        {
            label: 'Rate',
            value: service.rate
                ? `${service.rate.name}${service.rate.is_archived ? ' (archived)' : ''}`
                : 'Not on a rate, so not billed',
        },
        {
            label: 'Price',
            value:
                service.rate &&
                `${formatCents(service.rate.price_in_cents)} a month each`,
        },
        { label: 'Quantity', value: service.quantity },
        {
            label: `Rental per ${period}`,
            value:
                service.rental_in_cents != null &&
                `${formatCents(service.rental_in_cents)} ex GST`,
        },
        {
            label: 'Billing starts',
            value: formatDay(service.billing_starts_on),
        },
        {
            label: 'Billed',
            value:
                service.billing_period_in_months == null
                    ? `Every ${period}, as the rate`
                    : `Every ${period}`,
        },
        { label: 'Renews by itself', value: service.auto_renew ? 'Yes' : 'No' },
        {
            label: 'Expires',
            value: service.ends_on && formatDay(service.ends_on),
        },
    ];

    const customer_: Detail[] = [
        {
            label: 'Customer',
            value: (
                <Link
                    href={showCustomer(customer.id)}
                    className="hover:underline"
                >
                    {customer.name}
                </Link>
            ),
        },
        { label: 'Location', value: location?.name },
        { label: 'Contact', value: contact?.name },
        { label: 'Short name', value: service.short_name },
        ...customFields
            .filter((field) =>
                hasCustomFieldValue(field, service.custom_fields[field.id]),
            )
            .map((field) => ({
                label: field.name,
                value: (
                    <CustomFieldValueView
                        field={field}
                        value={service.custom_fields[field.id]}
                    />
                ),
            })),
    ];

    const record: Detail[] = [
        { label: 'Added', value: formatDay(dates.created_at) },
        { label: 'Changed', value: formatDay(dates.updated_at) },
        ...(service.archived_at
            ? [{ label: 'Archived', value: formatDay(service.archived_at) }]
            : []),
    ];

    return (
        <>
            <Head title={service.name} />

            <div className="@container flex flex-col gap-4">
                <div className="grid min-w-0 gap-4 @3xl:grid-cols-2">
                    <Panel title="Billing" details={billing} />
                    <Panel title="Customer" details={customer_} />
                </div>
                {service.note && (
                    <section className="rounded-xl border bg-card p-4 shadow-xs">
                        <h2 className="mb-2 text-sm font-medium">Note</h2>
                        <p className="text-sm whitespace-pre-line">
                            {service.note}
                        </p>
                    </section>
                )}
                <Panel title="Record" details={record} />
            </div>
        </>
    );
}
