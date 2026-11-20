import { Download } from 'lucide-react';
import NativeSelect from '@/components/native-select';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { visitWithQuery } from '@/lib/query';
import { exportMethod } from '@/routes/payments';
import type { Option } from '@/types';

export type PaymentListFilters = {
    search: string | null;
    method: string | null;
    from: string | null;
    to: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

/**
 * Search, method and paid-on range for a payments list, and a CSV of what
 * it shows.
 */
export default function PaymentFilters({
    filters,
    methods,
    reload,
    customerId,
}: {
    filters: PaymentListFilters;
    methods: Option[];
    reload: string[];
    /** Exports only this customer's payments. */
    customerId?: string;
}) {
    const filter = (key: string) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: reload });
    const exportQuery = {
        search: filters.search ?? undefined,
        method: filters.method ?? undefined,
        from: filters.from ?? undefined,
        to: filters.to ?? undefined,
        sort: filters.sort,
        direction: filters.direction,
        customer: customerId,
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <SearchInput
                value={filters.search}
                placeholder="Search by number, reference, notes or customer"
                only={reload}
            />
            <NativeSelect
                aria-label="Method"
                value={filters.method ?? ''}
                onChange={(event) => filter('method')(event.target.value)}
                className="h-8 w-auto text-[13px]"
            >
                <option value="">Any method</option>
                {methods.map((method) => (
                    <option key={method.value} value={method.value}>
                        {method.label}
                    </option>
                ))}
            </NativeSelect>
            <Input
                aria-label="Paid from"
                type="date"
                value={filters.from ?? ''}
                onChange={(event) => filter('from')(event.target.value)}
                className="h-8 w-auto text-[13px]"
            />
            <span className="text-xs text-muted-foreground">to</span>
            <Input
                aria-label="Paid to"
                type="date"
                value={filters.to ?? ''}
                onChange={(event) => filter('to')(event.target.value)}
                className="h-8 w-auto text-[13px]"
            />
            <Button variant="outline" size="sm" className="ml-auto" asChild>
                <a href={exportMethod.url({ query: exportQuery })}>
                    <Download />
                    Export CSV
                </a>
            </Button>
        </div>
    );
}
