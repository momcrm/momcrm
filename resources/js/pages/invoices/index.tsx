import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import InvoiceList from '@/components/invoices/invoice-list';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { create, index } from '@/routes/invoices';
import type { InvoiceListItem, Option, Paginated } from '@/types';

export default function InvoicesIndex({
    invoices,
    filters,
    statuses,
}: {
    invoices: Paginated<InvoiceListItem>;
    filters: { search: string | null; status: string | null };
    statuses: Option[];
}) {
    const tabs = [{ value: null, label: 'All' }, ...statuses];

    return (
        <>
            <Head title="Invoices" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Invoices"
                    description="What you've billed your customers, and what's still owed."
                    actions={
                        <Button asChild>
                            <Link href={create()}>Create invoice</Link>
                        </Button>
                    }
                />

                <div className="flex flex-wrap items-center gap-4">
                    <nav className="flex gap-1 rounded-lg bg-muted p-1">
                        {tabs.map((tab) => (
                            <Link
                                key={tab.label}
                                href={index({
                                    query: { status: tab.value ?? undefined },
                                })}
                                preserveState
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
                    <SearchInput
                        value={filters.search}
                        placeholder="Find by invoice number"
                        only={['invoices', 'filters']}
                    />
                </div>

                {invoices.data.length === 0 ? (
                    filters.search || filters.status ? (
                        <EmptyState
                            title="No matching invoices"
                            description="Try another number or status."
                        />
                    ) : (
                        <EmptyState
                            title="No invoices yet"
                            description="Bill a customer for their services or a job, then mark it sent and paid."
                            action={
                                <Button asChild>
                                    <Link href={create()}>Create invoice</Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <InvoiceList invoices={invoices.data} />
                        <Pager page={invoices} only="invoices" />
                    </>
                )}
            </div>
        </>
    );
}

InvoicesIndex.layout = {
    breadcrumbs: [{ title: 'Invoices', href: index() }],
};
