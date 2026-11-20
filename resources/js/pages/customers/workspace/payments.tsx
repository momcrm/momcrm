import { Head } from '@inertiajs/react';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import Pager from '@/components/pager';
import PaymentFilters from '@/components/payments/payment-filters';
import type { PaymentListFilters } from '@/components/payments/payment-filters';
import PaymentTable from '@/components/payments/payment-table';
import RecordPaymentButton from '@/components/payments/record-payment-button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { visitWithQuery } from '@/lib/query';
import { payments as paymentsTab } from '@/routes/customers';
import type { CustomerWorkspace, Option, Paginated, Payment } from '@/types';

const RELOAD = ['payments', 'filters'];

export default function CustomerPayments({
    workspace,
    payments,
    filters,
    methods,
}: {
    workspace: CustomerWorkspace;
    payments: Paginated<Payment>;
    filters: PaymentListFilters;
    methods: Option[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Payments',
        href: paymentsTab(customer.id),
    });
    const isFiltered = Boolean(
        filters.search || filters.method || filters.from || filters.to,
    );

    return (
        <>
            <Head title={`${customer.name} · Payments`} />
            <TabHeader
                title="Payments"
                action={<RecordPaymentButton customerId={customer.id} />}
            />

            {payments.data.length === 0 && !isFiltered ? (
                <EmptyState
                    title="No payments yet"
                    description={`Money ${customer.name} pays you will show up here once it's recorded.`}
                    action={<RecordPaymentButton customerId={customer.id} />}
                />
            ) : (
                <div className="flex flex-col gap-4">
                    <PaymentFilters
                        filters={filters}
                        methods={methods}
                        reload={RELOAD}
                        customerId={customer.id}
                    />
                    {payments.data.length === 0 ? (
                        <EmptyState
                            title="No matching payments"
                            description="Check the spelling, or clear a filter."
                        />
                    ) : (
                        <>
                            <PaymentTable
                                payments={payments.data}
                                showCustomer={false}
                                sort={{
                                    column: filters.sort,
                                    direction: filters.direction,
                                }}
                                onSortChange={({ column, direction }) =>
                                    visitWithQuery(
                                        { sort: column, direction },
                                        { only: RELOAD },
                                    )
                                }
                            />
                            <Pager page={payments} only="payments" />
                        </>
                    )}
                </div>
            )}
        </>
    );
}
