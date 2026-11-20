import { Head, usePage } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useMemo, useState } from 'react';
import { BankFeedBulkBar } from '@/components/bank-feeds/bank-feed-bulk';
import BankFeedFilters from '@/components/bank-feeds/bank-feed-filters';
import type { BankFeedListFilters } from '@/components/bank-feeds/bank-feed-filters';
import BankFeedTable from '@/components/bank-feeds/bank-feed-table';
import type { BankFeedTableActions } from '@/components/bank-feeds/bank-feed-table';
import ChooseCustomerDialog from '@/components/bank-feeds/choose-customer-dialog';
import ImportStatementDialog from '@/components/bank-feeds/import-statement-dialog';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import PaymentDialog, {
    usePaymentDialog,
} from '@/components/payments/payment-dialog';
import RecordPaymentDialog from '@/components/payments/record-payment-dialog';
import { useTicketSelection } from '@/components/tickets/ticket-bulk';
import { Button } from '@/components/ui/button';
import { visitWithQuery } from '@/lib/query';
import { index } from '@/routes/bank-feeds';
import type { BankAccount, BankTransaction, Option, Paginated } from '@/types';

const RELOAD = ['transactions', 'filters'];

export default function BankFeedsIndex({
    transactions,
    filters,
    accounts,
    statuses,
    limits,
    can,
}: {
    transactions: Paginated<BankTransaction>;
    filters: BankFeedListFilters;
    accounts: BankAccount[];
    statuses: Option[];
    limits: { files: number; rows: number };
    can: { import: boolean; apply: boolean };
}) {
    const { canManageBankFeeds } = usePage().props;
    const [isImporting, setIsImporting] = useState(false);
    const [choosing, setChoosing] = useState<BankTransaction | null>(null);
    const [recording, setRecording] = useState<BankTransaction | null>(null);
    const { openPayment } = usePaymentDialog();
    const actions = useMemo<BankFeedTableActions>(
        () => ({
            onChooseCustomer: setChoosing,
            onApply: setRecording,
            onOpenPayment: openPayment,
            canApply: can.apply,
        }),
        [openPayment, can.apply],
    );
    const selection = useTicketSelection(
        transactions.data.map((line) => line.id),
    );
    const hasImported = accounts.some(
        (account) => account.last_imported_at !== null,
    );
    const isFiltered = Boolean(
        filters.search || filters.account || filters.from || filters.to,
    );

    return (
        <>
            <Head title="Bank feeds" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Bank feeds"
                    description="Deposits from your bank statements. Import a statement, choose who paid each deposit, and record it as their payment."
                    actions={
                        can.import &&
                        accounts.length > 0 && (
                            <Button
                                size="sm"
                                onClick={() => setIsImporting(true)}
                            >
                                <Upload />
                                Import statement
                            </Button>
                        )
                    }
                />

                {accounts.length === 0 ? (
                    <EmptyState
                        title="No bank accounts yet"
                        description={
                            canManageBankFeeds
                                ? 'Add the account your customers pay into under Settings → Bank feeds, then import its statements here.'
                                : 'Ask a team owner or admin to add the account your customers pay into.'
                        }
                    />
                ) : (
                    <>
                        <BankFeedFilters
                            filters={filters}
                            statuses={statuses}
                            accounts={accounts}
                            reload={RELOAD}
                        />

                        {transactions.data.length === 0 ? (
                            isFiltered || filters.status !== 'pending' ? (
                                <EmptyState
                                    title="No matching lines"
                                    description="Check the spelling, or clear a filter."
                                />
                            ) : hasImported ? (
                                <EmptyState
                                    title="Nothing to match"
                                    description="Every deposit is dealt with. Import a newer statement to bring in more."
                                />
                            ) : (
                                <EmptyState
                                    title="Nothing imported yet"
                                    description="Export a statement from your online banking and import it here. Each deposit in it becomes a line to match to a customer."
                                />
                            )
                        ) : (
                            <>
                                <BankFeedTable
                                    transactions={transactions.data}
                                    selection={selection}
                                    actions={actions}
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
                                <Pager
                                    page={transactions}
                                    only="transactions"
                                />
                            </>
                        )}
                    </>
                )}
            </div>

            <BankFeedBulkBar selection={selection} canApply={can.apply} />

            <ChooseCustomerDialog
                line={choosing}
                onOpenChange={(open) => !open && setChoosing(null)}
            />

            {recording?.customer_id && (
                <RecordPaymentDialog
                    key={recording.id}
                    customerId={recording.customer_id}
                    bankLine={recording}
                    open
                    onOpenChange={(open) => !open && setRecording(null)}
                />
            )}

            <PaymentDialog />

            {accounts.length > 0 && (
                <ImportStatementDialog
                    open={isImporting}
                    onOpenChange={setIsImporting}
                    accounts={accounts}
                    maxFiles={limits.files}
                    maxRows={limits.rows}
                />
            )}
        </>
    );
}

BankFeedsIndex.layout = {
    breadcrumbs: [{ title: 'Bank feeds', href: index() }],
};
