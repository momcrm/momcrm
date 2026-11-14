import { router } from '@inertiajs/react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { show } from '@/routes/customers';
import type { CustomerListItem } from '@/types';

const column = dataTableColumns<CustomerListItem>();

const columns = column.columns([
    column.accessor('number', {
        header: '#',
        meta: { className: 'w-16' },
        cell: (info) => (
            <span className="font-mono text-xs text-subtle-foreground">
                {info.getValue()}
            </span>
        ),
    }),
    column.accessor('name', {
        header: 'Name',
        cell: (info) => (
            <span className="flex items-center gap-2">
                <span className="truncate font-medium">{info.getValue()}</span>
                {info.row.original.is_sample && (
                    <SoftBadge tone="neutral">Sample</SoftBadge>
                )}
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
    column.accessor('phone', {
        header: 'Phone',
        meta: { className: 'hidden lg:table-cell w-40' },
        cell: (info) => (
            <span className="text-muted-foreground tabular-nums">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.accessor('contacts_count', {
        header: 'Contacts',
        meta: { className: 'w-24 text-right' },
        cell: (info) => (
            <span className="text-muted-foreground tabular-nums">
                {info.getValue()}
            </span>
        ),
    }),
    column.accessor('status', {
        header: 'Status',
        meta: { className: 'w-28' },
        cell: (info) => (
            <StatusPill
                tone={info.getValue() === 'active' ? 'success' : 'neutral'}
            >
                {info.getValue() === 'active' ? 'Active' : 'Inactive'}
            </StatusPill>
        ),
    }),
]);

export default function CustomerTable({
    customers,
}: {
    customers: CustomerListItem[];
}) {
    return (
        <DataTable
            columns={columns}
            data={customers}
            getRowId={(customer) => customer.id}
            onRowClick={(customer) => router.visit(show.url(customer.id))}
        />
    );
}
