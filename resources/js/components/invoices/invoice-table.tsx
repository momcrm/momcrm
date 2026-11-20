import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import InvoiceDialog, {
    useInvoiceDialog,
} from '@/components/invoices/invoice-dialog';
import { Mail } from 'lucide-react';
import { useMemo } from 'react';
import StatusPill from '@/components/status-pill';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { formatCents } from '@/lib/money';
import type { InvoiceListItem, Tone } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

const statuses: Record<
    InvoiceListItem['status'],
    { label: string; tone: Tone }
> = {
    draft: { label: 'Draft', tone: 'neutral' },
    sent: { label: 'Sent', tone: 'info' },
    paid: { label: 'Paid', tone: 'success' },
    void: { label: 'Void', tone: 'neutral' },
};

const column = dataTableColumns<InvoiceListItem>();

const columns = column.columns([
    column.accessor('reference', {
        header: 'Invoice',
        meta: { className: 'w-32' },
        cell: (info) => (
            <span className="font-mono text-xs">{info.getValue()}</span>
        ),
    }),
    column.accessor('customer_name', {
        header: 'Customer',
        cell: (info) => (
            <span className="truncate font-medium">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.accessor('issued_on', {
        header: 'Issued',
        meta: { className: 'hidden md:table-cell w-32' },
        cell: (info) => (
            <span className="text-muted-foreground">
                {info.getValue()
                    ? dateFormatter.format(new Date(info.getValue()!))
                    : '—'}
            </span>
        ),
    }),
    column.accessor('due_on', {
        header: 'Due',
        meta: { className: 'w-32' },
        cell: (info) => (
            <span
                className={
                    info.row.original.is_overdue
                        ? 'font-medium text-destructive'
                        : 'text-muted-foreground'
                }
            >
                {info.getValue()
                    ? dateFormatter.format(new Date(info.getValue()!))
                    : '—'}
            </span>
        ),
    }),
    column.accessor('total_in_cents', {
        header: 'Total',
        meta: { className: 'w-32 text-right' },
        cell: (info) => (
            <span className="font-medium tabular-nums">
                {formatCents(info.getValue(), info.row.original.currency)}
            </span>
        ),
    }),
    column.accessor('balance_in_cents', {
        header: 'Owing',
        meta: { className: 'hidden sm:table-cell w-32 text-right' },
        cell: (info) =>
            info.row.original.status === 'sent' ? (
                <span className="tabular-nums">
                    {formatCents(info.getValue(), info.row.original.currency)}
                </span>
            ) : (
                <span className="text-muted-foreground">—</span>
            ),
    }),
    column.accessor('status', {
        header: 'Status',
        meta: { className: 'w-32' },
        cell: (info) => (
            <span className="flex items-center gap-1.5">
                <InvoiceStatus invoice={info.row.original} />
                {info.row.original.emailed_at && (
                    <Mail
                        className="size-3.5 text-subtle-foreground"
                        aria-label="Emailed"
                    >
                        <title>
                            Emailed{' '}
                            {dateFormatter.format(
                                new Date(info.row.original.emailed_at),
                            )}
                        </title>
                    </Mail>
                )}
            </span>
        ),
    }),
]);

function InvoiceStatus({ invoice }: { invoice: InvoiceListItem }) {
    return invoice.is_overdue ? (
        <StatusPill tone="destructive">Overdue</StatusPill>
    ) : invoice.status === 'sent' && invoice.paid_in_cents > 0 ? (
        <StatusPill tone="info">Part paid</StatusPill>
    ) : (
        <StatusPill tone={statuses[invoice.status].tone}>
            {statuses[invoice.status].label}
        </StatusPill>
    );
}

function selectColumn(selection: TicketSelection, rowIds: string[]) {
    const pickedCount = rowIds.filter((id) => selection.isSelected(id)).length;

    return column.display({
        id: 'select',
        meta: { className: 'w-9 pr-0' },
        header: () => (
            <SelectCheckbox
                label="Select all invoices on this page"
                checked={rowIds.length > 0 && pickedCount === rowIds.length}
                indeterminate={pickedCount > 0}
                onChange={(isOn) => selection.setMany(rowIds, isOn)}
            />
        ),
        cell: (info) => (
            <SelectCheckbox
                label={`Select ${info.row.original.reference}`}
                checked={selection.isSelected(info.row.original.id)}
                onChange={() => selection.toggle(info.row.original.id)}
            />
        ),
    });
}

export default function InvoiceTable({
    invoices,
    showCustomer = true,
    selection,
}: {
    invoices: InvoiceListItem[];
    showCustomer?: boolean;
    /** Lets invoices be picked for a bulk change. */
    selection?: TicketSelection;
}) {
    const { openInvoice } = useInvoiceDialog();
    const rowIds = invoices.map((invoice) => invoice.id);
    const shown = showCustomer
        ? columns
        : withoutColumn(columns, 'customer_name');
    const withSelection = useMemo(
        () => (selection ? [selectColumn(selection, rowIds), ...shown] : shown),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [selection, rowIds.join(','), showCustomer],
    );

    return (
        <>
            <DataTable
                columns={withSelection}
                data={invoices}
                getRowId={(invoice) => invoice.id}
                onRowClick={(invoice) => openInvoice(invoice.id)}
                isRowSelected={
                    selection
                        ? (invoice) => selection.isSelected(invoice.id)
                        : undefined
                }
            />
            <InvoiceDialog />
        </>
    );
}
