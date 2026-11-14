import { router } from '@inertiajs/react';
import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import SoftBadge from '@/components/soft-badge';
import { edit } from '@/routes/contacts';
import type { Contact } from '@/types';

type ContactRow = Contact & { customer_name?: string | null };

const column = dataTableColumns<ContactRow>();

const columns = column.columns([
    column.accessor('full_name', {
        header: 'Name',
        cell: (info) => (
            <span className="flex items-center gap-2">
                <span className="truncate font-medium">{info.getValue()}</span>
                {info.row.original.is_primary && (
                    <SoftBadge tone="primary">Primary</SoftBadge>
                )}
            </span>
        ),
    }),
    column.accessor('position', {
        header: 'Position',
        meta: { className: 'hidden lg:table-cell' },
        cell: (info) => (
            <span className="text-muted-foreground">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.accessor('customer_name', {
        header: 'Customer',
        cell: (info) => (
            <span className="text-muted-foreground">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.accessor('email', {
        header: 'Email',
        meta: { className: 'hidden md:table-cell' },
        cell: (info) => (
            <span className="text-muted-foreground">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.display({
        id: 'phone',
        header: 'Phone',
        meta: { className: 'hidden xl:table-cell w-40' },
        cell: (info) => (
            <span className="text-muted-foreground tabular-nums">
                {info.row.original.work_phone ??
                    info.row.original.mobile_phone ??
                    '—'}
            </span>
        ),
    }),
]);

export default function ContactTable({
    contacts,
    showCustomer = true,
}: {
    contacts: ContactRow[];
    showCustomer?: boolean;
}) {
    return (
        <DataTable
            columns={
                showCustomer ? columns : withoutColumn(columns, 'customer_name')
            }
            data={contacts}
            getRowId={(contact) => contact.id}
            onRowClick={(contact) => router.visit(edit.url(contact.id))}
        />
    );
}
