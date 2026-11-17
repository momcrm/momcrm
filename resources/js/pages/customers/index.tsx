import { Head, Link } from '@inertiajs/react';
import { Columns3 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    isFilterableCustomField,
    useCustomFields,
} from '@/components/custom-fields/custom-field-input';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    /** Filters on custom fields, by field id. */
    custom_fields: Record<string, string>;
};

const COLUMNS_KEY = 'customers.custom-field-columns';

/**
 * Which custom fields this browser shows as columns in the customer table.
 * A per-viewer convenience, so it lives in local storage.
 */
function useCustomFieldColumns(): [string[], (fieldId: string) => void] {
    const [shown, setShown] = useState<string[]>(() => {
        try {
            const stored: unknown = JSON.parse(
                window.localStorage.getItem(COLUMNS_KEY) ?? '[]',
            );

            return Array.isArray(stored)
                ? stored.filter((id) => typeof id === 'string')
                : [];
        } catch {
            return [];
        }
    });

    const toggle = (fieldId: string) => {
        const next = shown.includes(fieldId)
            ? shown.filter((id) => id !== fieldId)
            : [...shown, fieldId];

        setShown(next);

        try {
            window.localStorage.setItem(COLUMNS_KEY, JSON.stringify(next));
        } catch {
            // Private windows can refuse storage; the choice just won't stick.
        }
    };

    return [shown, toggle];
}

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
    const customFields = useCustomFields('customer');
    const [shownColumns, toggleColumn] = useCustomFieldColumns();
    const isFiltered = Boolean(
        filters.search ||
        filters.type ||
        filters.billing ||
        filters.status ||
        Object.keys(filters.custom_fields).length > 0,
    );
    const filter = (key: string) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: RELOAD });

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
                        {customFields
                            .filter(isFilterableCustomField)
                            .map((field) => (
                                <FilterSelect
                                    key={field.id}
                                    label={field.name}
                                    value={
                                        filters.custom_fields[field.id] ?? null
                                    }
                                    onChange={filter(`cf[${field.id}]`)}
                                >
                                    <option value="">Any {field.name}</option>
                                    {field.type === 'checkbox' ? (
                                        <>
                                            <option value="1">
                                                {field.name}: yes
                                            </option>
                                            <option value="0">
                                                {field.name}: no
                                            </option>
                                        </>
                                    ) : (
                                        <>
                                            <option value="none">
                                                No {field.name}
                                            </option>
                                            {field.options.map((option) => (
                                                <option
                                                    key={option.id}
                                                    value={option.id}
                                                >
                                                    {option.name}
                                                </option>
                                            ))}
                                        </>
                                    )}
                                </FilterSelect>
                            ))}
                        {customFields.length > 0 && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Columns3 />
                                        Columns
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="start"
                                    className="w-48"
                                >
                                    {customFields.map((field) => (
                                        <DropdownMenuCheckboxItem
                                            key={field.id}
                                            checked={shownColumns.includes(
                                                field.id,
                                            )}
                                            onSelect={(event) =>
                                                event.preventDefault()
                                            }
                                            onCheckedChange={() =>
                                                toggleColumn(field.id)
                                            }
                                        >
                                            {field.name}
                                        </DropdownMenuCheckboxItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
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
                            customFields={customFields.filter((field) =>
                                shownColumns.includes(field.id),
                            )}
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
