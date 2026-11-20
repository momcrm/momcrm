import { router } from '@inertiajs/react';
import {
    Banknote,
    EyeOff,
    MoreHorizontal,
    RotateCcw,
    Trash2,
    UserRound,
} from 'lucide-react';
import { useMemo } from 'react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import StatusPill from '@/components/status-pill';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCents } from '@/lib/money';
import { destroy, hide } from '@/routes/bank-feeds';
import type { BankTransaction } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

const column = dataTableColumns<BankTransaction>();

export function hideLines(
    ids: string[],
    hidden: boolean,
    onSuccess?: () => void,
) {
    router.patch(
        hide.url(),
        { bank_transaction_ids: ids, hidden },
        { preserveScroll: true, onSuccess },
    );
}

export function deleteLines(ids: string[], onSuccess?: () => void) {
    router.delete(destroy.url(), {
        data: { bank_transaction_ids: ids },
        preserveScroll: true,
        onSuccess,
    });
}

export type BankFeedTableActions = {
    onChooseCustomer: (line: BankTransaction) => void;
    onApply: (line: BankTransaction) => void;
    onOpenPayment: (paymentId: string) => void;
    canApply: boolean;
};

function LineActions({
    line,
    actions,
}: {
    line: BankTransaction;
    actions: BankFeedTableActions;
}) {
    const isPending = line.status === 'pending';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`Actions for ${line.description}`}
                >
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {isPending && actions.canApply && line.customer_id && (
                    <DropdownMenuItem onSelect={() => actions.onApply(line)}>
                        <Banknote />
                        Record as payment…
                    </DropdownMenuItem>
                )}
                {isPending && (
                    <DropdownMenuItem
                        onSelect={() => actions.onChooseCustomer(line)}
                    >
                        <UserRound />
                        {line.customer_id
                            ? 'Change customer…'
                            : 'Choose customer…'}
                    </DropdownMenuItem>
                )}
                {line.payment_id && (
                    <DropdownMenuItem
                        onSelect={() => actions.onOpenPayment(line.payment_id!)}
                    >
                        <Banknote />
                        Open {line.payment_reference_number}
                    </DropdownMenuItem>
                )}
                {isPending && <DropdownMenuSeparator />}
                {line.status === 'applied' ? null : line.status === 'hidden' ? (
                    <DropdownMenuItem
                        onSelect={() => hideLines([line.id], false)}
                    >
                        <RotateCcw />
                        Back to pending
                    </DropdownMenuItem>
                ) : (
                    <DropdownMenuItem
                        onSelect={() => hideLines([line.id], true)}
                    >
                        <EyeOff />
                        Hide
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem
                    variant="destructive"
                    onSelect={() => deleteLines([line.id])}
                >
                    <Trash2 />
                    Delete
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function CustomerCell({
    line,
    actions,
}: {
    line: BankTransaction;
    actions: BankFeedTableActions;
}) {
    if (line.status !== 'pending') {
        return line.customer_name ? (
            <span className="truncate">{line.customer_name}</span>
        ) : (
            <span className="text-subtle-foreground">—</span>
        );
    }

    return (
        <div className="flex min-w-0 items-center gap-1.5">
            <div className="min-w-0">
                <button
                    type="button"
                    onClick={() => actions.onChooseCustomer(line)}
                    className={
                        line.customer_name
                            ? 'block max-w-full truncate text-left underline-offset-4 hover:underline'
                            : 'text-left text-primary underline-offset-4 hover:underline'
                    }
                >
                    {line.customer_name ?? 'Choose…'}
                </button>
                {line.matched_by && line.matched_by !== 'manual' && (
                    <p className="truncate text-xs text-muted-foreground">
                        {line.matched_by_label}
                    </p>
                )}
            </div>
            {line.customer_id && actions.canApply && (
                <Button
                    variant="outline"
                    size="sm"
                    className="h-6 shrink-0 px-2 text-xs"
                    onClick={() => actions.onApply(line)}
                >
                    Record
                </Button>
            )}
        </div>
    );
}

function buildColumns(
    selection: TicketSelection,
    rowIds: string[],
    actions: BankFeedTableActions,
) {
    const pickedCount = rowIds.filter((id) => selection.isSelected(id)).length;

    return column.columns([
        column.display({
            id: 'select',
            meta: { className: 'w-9 pr-0' },
            header: () => (
                <SelectCheckbox
                    label="Select all lines on this page"
                    checked={rowIds.length > 0 && pickedCount === rowIds.length}
                    indeterminate={pickedCount > 0}
                    onChange={(isOn) => selection.setMany(rowIds, isOn)}
                />
            ),
            cell: (info) => (
                <SelectCheckbox
                    label={`Select ${info.row.original.description}`}
                    checked={selection.isSelected(info.row.original.id)}
                    onChange={() => selection.toggle(info.row.original.id)}
                />
            ),
        }),
        column.accessor('posted_on', {
            header: 'Date',
            enableSorting: true,
            meta: { className: 'w-32' },
            cell: (info) => (
                <span className="text-muted-foreground">
                    {info.getValue()
                        ? dateFormatter.format(new Date(info.getValue()!))
                        : '—'}
                </span>
            ),
        }),
        column.accessor('description', {
            header: 'Description',
            cell: (info) => (
                <div className="min-w-0">
                    <p className="truncate font-medium">{info.getValue()}</p>
                    {(info.row.original.payer_name ||
                        info.row.original.crn) && (
                        <p className="truncate text-xs text-muted-foreground">
                            {[
                                info.row.original.payer_name,
                                info.row.original.crn &&
                                    `CRN ${info.row.original.crn}`,
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    )}
                </div>
            ),
        }),
        column.accessor('bank_account_name', {
            header: 'Account',
            meta: { className: 'hidden lg:table-cell w-44' },
            cell: (info) => (
                <span className="truncate text-muted-foreground">
                    {info.getValue() ?? '—'}
                </span>
            ),
        }),
        column.accessor('customer_name', {
            header: 'Customer',
            meta: { className: 'hidden md:table-cell w-64' },
            cell: (info) => (
                <CustomerCell line={info.row.original} actions={actions} />
            ),
        }),
        column.accessor('amount_in_cents', {
            header: 'Amount',
            enableSorting: true,
            meta: { className: 'w-32 text-right' },
            cell: (info) => (
                <span className="font-medium tabular-nums">
                    {formatCents(
                        info.getValue(),
                        info.row.original.currency ?? undefined,
                    )}
                </span>
            ),
        }),
        column.accessor('status_label', {
            header: 'Status',
            meta: { className: 'hidden sm:table-cell w-28' },
            cell: (info) => (
                <StatusPill
                    tone={
                        info.row.original.status === 'applied'
                            ? 'success'
                            : info.row.original.status === 'hidden'
                              ? 'neutral'
                              : 'warning'
                    }
                >
                    {info.getValue()}
                </StatusPill>
            ),
        }),
        column.display({
            id: 'actions',
            meta: { className: 'w-10 text-right' },
            cell: (info) => (
                <LineActions line={info.row.original} actions={actions} />
            ),
        }),
    ]);
}

/**
 * Bank feed lines as a table, each with a checkbox for bulk changes, its
 * customer to pick, and a menu to record, hide or delete it.
 */
export default function BankFeedTable({
    transactions,
    selection,
    sort,
    onSortChange,
    actions,
}: {
    transactions: BankTransaction[];
    selection: TicketSelection;
    actions: BankFeedTableActions;
    sort: DataTableSort;
    onSortChange: (sort: DataTableSort) => void;
}) {
    const rowIds = transactions.map((line) => line.id);
    const columns = useMemo(
        () => buildColumns(selection, rowIds, actions),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [selection, rowIds.join(','), actions],
    );

    return (
        <DataTable
            columns={columns}
            data={transactions}
            getRowId={(line) => line.id}
            sort={sort}
            onSortChange={onSortChange}
            isRowSelected={(line) => selection.isSelected(line.id)}
        />
    );
}
