import { Head, Link } from '@inertiajs/react';
import CustomerTable from '@/components/customers/customer-table';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { create, importMethod as importRoute, index } from '@/routes/customers';
import type { CustomerListItem, Paginated } from '@/types';

export default function CustomersIndex({
    customers,
    filters,
}: {
    customers: Paginated<CustomerListItem>;
    filters: { search: string | null };
}) {
    const isFiltered = Boolean(filters.search);

    return (
        <>
            <Head title="Customers" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Customers"
                    description="The businesses and people you work for."
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={importRoute()}>Import</Link>
                            </Button>
                            <Button asChild>
                                <Link href={create()}>Add customer</Link>
                            </Button>
                        </>
                    }
                />

                {(customers.total > 0 || isFiltered) && (
                    <SearchInput
                        value={filters.search}
                        placeholder="Search customers"
                        only={['customers', 'filters']}
                    />
                )}

                {customers.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title={`No matches for "${filters.search}"`}
                            description="Check the spelling or try a shorter search."
                        />
                    ) : (
                        <EmptyState
                            title="No customers yet"
                            description="Add the businesses and people you work for. Contacts, sites and tickets all hang off a customer."
                            action={
                                <Button asChild>
                                    <Link href={create()}>Add customer</Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <CustomerTable customers={customers.data} />
                        <Pager page={customers} only="customers" />
                    </>
                )}
            </div>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Customers', href: index() }],
};
