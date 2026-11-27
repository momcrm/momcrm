import { Head, Link } from '@inertiajs/react';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { useCreateInvoice } from '@/components/invoices/create-invoice-dialog';
import InvoiceTable from '@/components/invoices/invoice-table';
import Pager from '@/components/pager';
import RecordPaymentButton from '@/components/payments/record-payment-button';
import { Button } from '@/components/ui/button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { cn } from '@/lib/utils';
import { invoices as invoicesTab } from '@/routes/customers';
import type {
    CustomerWorkspace,
    InvoiceListItem,
    Option,
    Paginated,
} from '@/types';

export default function CustomerInvoices({
    workspace,
    invoices,
    filters,
    statuses,
}: {
    workspace: CustomerWorkspace;
    invoices: Paginated<InvoiceListItem>;
    filters: { status: string | null };
    statuses: Option[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Invoices',
        href: invoicesTab(customer.id),
    });
    const tabs = [{ value: null, label: 'All' }, ...statuses];
    const { openCreateInvoice } = useCreateInvoice();
    const add = (
        <Button
            variant="outline"
            size="sm"
            onClick={() => openCreateInvoice({ customerId: customer.id })}
        >
            Create invoice
        </Button>
    );
    const actions = (
        <div className="flex flex-wrap gap-2">
            <RecordPaymentButton customerId={customer.id} />
            {add}
        </div>
    );

    return (
        <>
            <Head title={`${customer.name} · Invoices`} />
            <TabHeader title="Invoices" action={actions} />

            <nav className="mb-4 flex w-fit gap-1 rounded-lg bg-muted p-1">
                {tabs.map((tab) => (
                    <Link
                        key={tab.label}
                        href={invoicesTab(customer.id, {
                            query: { status: tab.value ?? undefined },
                        })}
                        only={['invoices', 'filters']}
                        preserveState
                        preserveScroll
                        className={cn(
                            'rounded-md px-3 py-1 text-sm',
                            filters.status === tab.value
                                ? 'bg-background shadow-xs'
                                : 'text-muted-foreground',
                        )}
                    >
                        {tab.label}
                    </Link>
                ))}
            </nav>

            {invoices.data.length > 0 ? (
                <>
                    <InvoiceTable
                        invoices={invoices.data}
                        showCustomer={false}
                    />
                    <Pager page={invoices} only="invoices" />
                </>
            ) : filters.status ? (
                <EmptyState
                    title="No matching invoices"
                    description="Try another status."
                />
            ) : (
                <EmptyState
                    title="No invoices yet"
                    description={`Invoices for ${customer.name} will show up here once you bill them.`}
                    action={add}
                />
            )}
        </>
    );
}
