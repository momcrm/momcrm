import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import InvoiceDialog, {
    useInvoiceDialog,
} from '@/components/invoices/invoice-dialog';
import StatusPill from '@/components/status-pill';
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
    column.accessor('status', {
        header: 'Status',
        meta: { className: 'w-28' },
        cell: (info) =>
            info.row.original.is_overdue ? (
                <StatusPill tone="destructive">Overdue</StatusPill>
            ) : (
                <StatusPill tone={statuses[info.getValue()].tone}>
                    {statuses[info.getValue()].label}
                </StatusPill>
            ),
    }),
]);

export default function InvoiceTable({
    invoices,
    showCustomer = true,
}: {
    invoices: InvoiceListItem[];
    showCustomer?: boolean;
}) {
    const { openInvoice } = useInvoiceDialog();

    return (
        <>
            <DataTable
                columns={
                    showCustomer
                        ? columns
                        : withoutColumn(columns, 'customer_name')
                }
                data={invoices}
                getRowId={(invoice) => invoice.id}
                onRowClick={(invoice) => openInvoice(invoice.id)}
            />
            <InvoiceDialog />
        </>
    );
}
