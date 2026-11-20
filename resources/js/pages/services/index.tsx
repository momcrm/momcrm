import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import EmptyState from '@/components/empty-state';
import NativeSelect from '@/components/native-select';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import RateCombobox from '@/components/rates/rate-combobox';
import SearchInput from '@/components/search-input';
import { ServiceBulkBar } from '@/components/services/service-bulk';
import ServiceTable from '@/components/services/service-table';
import { useTicketSelection } from '@/components/tickets/ticket-bulk';
import { visitWithQuery } from '@/lib/query';
import { index } from '@/routes/services';
import type {
    CustomerOption,
    Option,
    Paginated,
    Rate,
    ServiceListItem,
} from '@/types';

type Filters = {
    search: string | null;
    status: string | null;
    rate: string | null;
    customer: string | null;
    auto_renew: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

const RELOAD = ['services', 'filters'];

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

export default function ServicesIndex({
    services,
    filters,
    statuses,
    rates,
    customers,
    billingPeriods,
}: {
    services: Paginated<ServiceListItem>;
    filters: Filters;
    statuses: Option[];
    rates: Rate[];
    customers: CustomerOption[];
    billingPeriods: number[];
}) {
    const selection = useTicketSelection(
        services.data.map((service) => service.id),
    );
    const isFiltered = Boolean(
        filters.search ||
        filters.status ||
        filters.rate ||
        filters.customer ||
        filters.auto_renew,
    );
    const filter = (key: string) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: RELOAD });

    return (
        <>
            <Head title="Services" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Services"
                    description="Everything your customers get from you, and what it's billed at. Add a service from its customer."
                />

                <div className="flex flex-wrap items-center gap-2">
                    <SearchInput
                        value={filters.search}
                        placeholder="Search by name, short name or customer"
                        only={RELOAD}
                    />
                    <FilterSelect
                        label="Status"
                        value={filters.status}
                        onChange={filter('status')}
                    >
                        <option value="">Active and inactive</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FilterSelect>
                    <RateCombobox
                        aria-label="Rate"
                        rates={rates}
                        value={filters.rate ?? ''}
                        onValueChange={filter('rate')}
                        emptyLabel="Any rate"
                        className="h-8 w-56 text-[13px]"
                    />
                    <FilterSelect
                        label="Customer"
                        value={filters.customer}
                        onChange={filter('customer')}
                    >
                        <option value="">Any customer</option>
                        {customers.map((customer) => (
                            <option key={customer.id} value={customer.id}>
                                {customer.name}
                            </option>
                        ))}
                    </FilterSelect>
                    <FilterSelect
                        label="Auto-renew"
                        value={filters.auto_renew}
                        onChange={filter('auto_renew')}
                    >
                        <option value="">Renewing or not</option>
                        <option value="1">Renews by itself</option>
                        <option value="0">Doesn't renew</option>
                    </FilterSelect>
                </div>

                {services.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title="No matching services"
                            description="Check the spelling, or clear a filter."
                        />
                    ) : (
                        <EmptyState
                            title="No services yet"
                            description="Open a customer and add what they get from you, priced from one of your rates."
                        />
                    )
                ) : (
                    <>
                        <ServiceTable
                            services={services.data}
                            selection={selection}
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
                        <Pager page={services} only="services" />
                        <ServiceBulkBar
                            selection={selection}
                            rates={rates}
                            billingPeriods={billingPeriods}
                            showsArchived={filters.status === 'archived'}
                        />
                    </>
                )}
            </div>
        </>
    );
}

ServicesIndex.layout = {
    breadcrumbs: [{ title: 'Services', href: index() }],
};
