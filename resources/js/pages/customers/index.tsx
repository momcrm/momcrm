import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { CustomerBulkBar } from '@/components/customers/customer-bulk';
import {
    billingTypeLabels,
    useCustomerTypes,
} from '@/components/customers/customer-meta';
import CustomerTable from '@/components/customers/customer-table';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import NativeSelect from '@/components/native-select';
import SearchInput from '@/components/search-input';
import { useTicketSelection } from '@/components/tickets/ticket-bulk';
import { Button } from '@/components/ui/button';
import { visitWithQuery } from '@/lib/query';
import { create, importMethod as importRoute, index } from '@/routes/customers';
import type {
    CustomerBillingType,
    CustomerListItem,
    Option,
    Paginated,
} from '@/types';

type Filters = {
    search: string | null;
    type: string | null;
    billing: CustomerBillingType | null;
    status: string | null;
};

const RELOAD = ['customers', 'filters'];

function FilterSelect({
    label,
    value,
    onChange,
    children,
}: {
    label: string;
    value: string | null;
    onChange: (value: string) => void;
    children: ReactNode;
}) {
    return (
        <NativeSelect
            aria-label={label}
            value={value ?? ''}
            onChange={(event) => onChange(event.target.value)}
            className="h-8 w-auto text-[13px]"
        >
            {children}
        </NativeSelect>
    );
}

export default function CustomersIndex({
    customers,
    filters,
    billingTypes,
    statuses,
}: {
    customers: Paginated<CustomerListItem>;
    filters: Filters;
    billingTypes: Option[];
    statuses: Option[];
}) {
    const types = useCustomerTypes(filters.type);
    const selection = useTicketSelection(
        customers.data.map((customer) => customer.id),
    );
    const isFiltered = Boolean(
        filters.search || filters.type || filters.billing || filters.status,
    );
    const filter = (key: keyof Filters) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: RELOAD });

    return (
        <>
            <Head title="Customers" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
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
                    <div className="flex flex-wrap items-center gap-2">
                        <SearchInput
                            value={filters.search}
                            placeholder="Search by name, prefix or email"
                            only={RELOAD}
                        />
                        <FilterSelect
                            label="Type"
                            value={filters.type}
                            onChange={filter('type')}
                        >
                            <option value="">Any type</option>
                            {types.map((type) => (
                                <option key={type.id} value={type.id}>
                                    {type.name}
                                </option>
                            ))}
                        </FilterSelect>
                        <FilterSelect
                            label="Billing"
                            value={filters.billing}
                            onChange={filter('billing')}
                        >
                            <option value="">Any billing</option>
                            {billingTypes.map((billingType) => (
                                <option
                                    key={billingType.value}
                                    value={billingType.value}
                                >
                                    {
                                        billingTypeLabels[
                                            billingType.value as CustomerBillingType
                                        ]
                                    }
                                </option>
                            ))}
                        </FilterSelect>
                        <FilterSelect
                            label="Status"
                            value={filters.status}
                            onChange={filter('status')}
                        >
                            <option value="">Any status</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </FilterSelect>
                    </div>
                )}

                {customers.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title="No matching customers"
                            description="Check the spelling, or clear a filter."
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
                        <CustomerTable
                            customers={customers.data}
                            selection={selection}
                        />
                        <Pager page={customers} only="customers" />
                        <CustomerBulkBar
                            selection={selection}
                            billingTypes={billingTypes}
                            statuses={statuses}
                        />
                    </>
                )}
            </div>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Customers', href: index() }],
};
