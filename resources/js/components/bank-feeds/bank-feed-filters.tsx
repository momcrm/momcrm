import NativeSelect from '@/components/native-select';
import SearchInput from '@/components/search-input';
import { Input } from '@/components/ui/input';
import { visitWithQuery } from '@/lib/query';
import type { BankAccount, Option } from '@/types';

export type BankFeedListFilters = {
    status: string;
    account: string | null;
    search: string | null;
    from: string | null;
    to: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

/**
 * Status, account, search and date range for the bank feed.
 */
export default function BankFeedFilters({
    filters,
    statuses,
    accounts,
    reload,
}: {
    filters: BankFeedListFilters;
    statuses: Option[];
    accounts: BankAccount[];
    reload: string[];
}) {
    const filter = (key: string) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: reload });

    const control = 'h-control-sm w-auto text-xs md:text-xs';

    return (
        <div className="flex flex-wrap items-center gap-2">
            <SearchInput
                value={filters.search}
                placeholder="Search description, customer or amount"
                only={reload}
                size="sm"
            />
            <NativeSelect
                aria-label="Status"
                value={filters.status}
                onChange={(event) => filter('status')(event.target.value)}
                className={control}
            >
                {statuses.map((status) => (
                    <option key={status.value} value={status.value}>
                        {status.label}
                    </option>
                ))}
                <option value="all">Any status</option>
            </NativeSelect>
            {accounts.length > 1 && (
                <NativeSelect
                    aria-label="Account"
                    value={filters.account ?? ''}
                    onChange={(event) => filter('account')(event.target.value)}
                    className={control}
                >
                    <option value="">Every account</option>
                    {accounts.map((account) => (
                        <option key={account.id} value={account.id}>
                            {account.name}
                        </option>
                    ))}
                </NativeSelect>
            )}
            <div className="flex items-center gap-1.5">
                <Input
                    aria-label="From"
                    type="date"
                    value={filters.from ?? ''}
                    onChange={(event) => filter('from')(event.target.value)}
                    className={control}
                />
                <span className="text-xs text-muted-foreground">–</span>
                <Input
                    aria-label="To"
                    type="date"
                    value={filters.to ?? ''}
                    onChange={(event) => filter('to')(event.target.value)}
                    className={control}
                />
            </div>
        </div>
    );
}
