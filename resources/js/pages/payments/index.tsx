import { Head } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import PaymentFilters from '@/components/payments/payment-filters';
import type { PaymentListFilters } from '@/components/payments/payment-filters';
import PaymentTable from '@/components/payments/payment-table';
import { visitWithQuery } from '@/lib/query';
import { index } from '@/routes/payments';
import type { Option, Paginated, Payment } from '@/types';

const RELOAD = ['payments', 'filters'];

export default function PaymentsIndex({
    payments,
    filters,
    methods,
}: {
    payments: Paginated<Payment>;
    filters: PaymentListFilters;
    methods: Option[];
}) {
    const isFiltered = Boolean(
        filters.search || filters.method || filters.from || filters.to,
    );

    return (
        <>
            <Head title="Payments" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Payments"
                    description="Money your customers have paid you, and the invoices it went to. Record a payment from its customer or invoice."
                />

                <PaymentFilters
                    filters={filters}
                    methods={methods}
                    reload={RELOAD}
                />

                {payments.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title="No matching payments"
                            description="Check the spelling, or clear a filter."
                        />
                    ) : (
                        <EmptyState
                            title="No payments yet"
                            description="When a customer pays, record it from their invoice and it shows up here."
                        />
                    )
                ) : (
                    <>
                        <PaymentTable
                            payments={payments.data}
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
        </>
    );
}

PaymentsIndex.layout = {
    breadcrumbs: [{ title: 'Payments', href: index() }],
};
