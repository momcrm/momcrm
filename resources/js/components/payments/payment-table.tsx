import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import PaymentDialog, {
    usePaymentDialog,
} from '@/components/payments/payment-dialog';
import { formatCents } from '@/lib/money';
import type { Payment } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

const column = dataTableColumns<Payment>();

const columns = column.columns([
    column.accessor('number', {
        header: 'Payment',
        enableSorting: true,
        meta: { className: 'w-32' },
        cell: (info) => (
            <span className="font-mono text-xs">
                {info.row.original.reference_number}
            </span>
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
    column.accessor('paid_on', {
        header: 'Paid on',
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
    column.accessor('method_label', {
        header: 'Method',
        meta: { className: 'hidden md:table-cell w-40' },
        cell: (info) => (
            <span className="text-muted-foreground">{info.getValue()}</span>
        ),
    }),
    column.accessor('reference', {
        header: 'Reference',
        meta: { className: 'hidden lg:table-cell' },
        cell: (info) => (
            <span className="truncate text-muted-foreground">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.accessor('amount_in_cents', {
        header: 'Amount',
        enableSorting: true,
        meta: { className: 'w-32 text-right' },
        cell: (info) => (
            <span className="font-medium tabular-nums">
                {formatCents(info.getValue(), info.row.original.currency)}
            </span>
        ),
    }),
    column.accessor('unallocated_in_cents', {
        header: 'Not applied',
        meta: { className: 'hidden sm:table-cell w-32 text-right' },
        cell: (info) =>
            info.getValue() > 0 ? (
                <span className="tabular-nums">
                    {formatCents(info.getValue(), info.row.original.currency)}
                </span>
            ) : (
                <span className="text-muted-foreground">—</span>
            ),
    }),
]);

/**
 * Payments as a table; a row opens the payment over the page.
 */
export default function PaymentTable({
    payments,
    showCustomer = true,
    sort,
    onSortChange,
}: {
    payments: Payment[];
    showCustomer?: boolean;
    sort?: DataTableSort;
    onSortChange?: (sort: DataTableSort) => void;
}) {
    const { openPayment } = usePaymentDialog();

    return (
        <>
            <DataTable
                columns={
                    showCustomer
                        ? columns
                        : withoutColumn(columns, 'customer_name')
                }
                data={payments}
                getRowId={(payment) => payment.id}
                onRowClick={(payment) => openPayment(payment.id)}
                sort={sort}
                onSortChange={onSortChange}
            />
            <PaymentDialog />
        </>
    );
}
