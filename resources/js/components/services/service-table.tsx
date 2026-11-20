import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import { billingPeriodLabel } from '@/components/rates/billing-period';
import {
    formatDay,
    serviceLifecycleLabels,
} from '@/components/services/service-detail';
import StatusPill from '@/components/status-pill';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { timeAgo } from '@/lib/format';
import { formatCents } from '@/lib/money';
import { show } from '@/routes/services';
import type { ServiceListItem } from '@/types';

const column = dataTableColumns<ServiceListItem>();

const dash = <span className="text-subtle-foreground">—</span>;

function buildColumns(selection: TicketSelection, rowIds: string[]) {
    const pickedCount = rowIds.filter((id) => selection.isSelected(id)).length;

    return column.columns([
        column.display({
            id: 'select',
            meta: { className: 'w-9 pr-0' },
            header: () => (
                <SelectCheckbox
                    label="Select all services on this page"
                    checked={rowIds.length > 0 && pickedCount === rowIds.length}
                    indeterminate={pickedCount > 0}
                    onChange={(isOn) => selection.setMany(rowIds, isOn)}
                />
            ),
            cell: (info) => (
                <SelectCheckbox
                    label={`Select ${info.row.original.name}`}
                    checked={selection.isSelected(info.row.original.id)}
                    onChange={() => selection.toggle(info.row.original.id)}
                />
            ),
        }),
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: (info) => (
                <span className="flex min-w-0 flex-col">
                    <span className="truncate font-medium">
                        {info.getValue()}
                    </span>
                    {info.row.original.short_name && (
                        <span className="truncate font-mono text-[11px] text-subtle-foreground">
                            {info.row.original.short_name}
                        </span>
                    )}
                </span>
            ),
        }),
        column.accessor('customer_name', {
            id: 'customer',
            header: 'Customer',
            enableSorting: true,
            meta: { className: 'w-44' },
            cell: (info) => (
                <span className="truncate">{info.getValue() ?? '—'}</span>
            ),
        }),
        column.accessor((service) => service.rate?.name, {
            id: 'rate',
            header: 'Rate',
            meta: { className: 'hidden md:table-cell w-40' },
            cell: (info) =>
                info.getValue() ? (
                    <span className="truncate text-muted-foreground">
                        {info.getValue()}
                    </span>
                ) : (
                    dash
                ),
        }),
        column.accessor('quantity', {
            header: 'Qty',
            meta: { className: 'hidden md:table-cell w-14 text-right' },
            cell: (info) => (
                <span className="tabular-nums">{info.getValue()}</span>
            ),
        }),
        column.accessor('rental_in_cents', {
            header: 'Rental',
            meta: { className: 'w-36 text-right' },
            cell: (info) =>
                info.getValue() == null ? (
                    dash
                ) : (
                    <span className="tabular-nums">
                        {formatCents(
                            info.getValue(),
                            info.row.original.currency,
                        )}
                        <span className="text-xs text-muted-foreground">
                            {' '}
                            /{' '}
                            {billingPeriodLabel(
                                info.row.original
                                    .effective_billing_period_in_months,
                            )}
                        </span>
                    </span>
                ),
        }),
        column.accessor('lifecycle', {
            header: 'Status',
            meta: { className: 'w-28' },
            cell: (info) => (
                <StatusPill
                    tone={
                        info.getValue() === 'active'
                            ? 'success'
                            : info.getValue() === 'inactive'
                              ? 'warning'
                              : 'neutral'
                    }
                >
                    {serviceLifecycleLabels[info.getValue()]}
                </StatusPill>
            ),
        }),
        column.accessor('auto_renew', {
            header: 'Auto-renew',
            meta: { className: 'hidden lg:table-cell w-24' },
            cell: (info) =>
                info.getValue() ? (
                    <span className="text-muted-foreground">Yes</span>
                ) : (
                    dash
                ),
        }),
        column.accessor((service) => service.next_invoice?.starts_on, {
            id: 'next_invoice',
            header: 'Next invoice',
            meta: { className: 'hidden lg:table-cell w-28' },
            cell: (info) =>
                info.getValue() ? (
                    <span className="text-muted-foreground">
                        {formatDay(info.getValue() ?? null)}
                    </span>
                ) : (
                    dash
                ),
        }),
        column.accessor('location_name', {
            header: 'Location',
            meta: { className: 'hidden xl:table-cell w-36' },
            cell: (info) =>
                info.getValue() ? (
                    <span className="truncate text-muted-foreground">
                        {info.getValue()}
                    </span>
                ) : (
                    dash
                ),
        }),
        column.accessor('updated_at', {
            header: 'Updated',
            enableSorting: true,
            meta: { className: 'hidden xl:table-cell w-28' },
            cell: (info) => (
                <span className="text-muted-foreground">
                    {timeAgo(info.getValue())}
                </span>
            ),
        }),
    ]);
}

/**
 * Every customer's services, with a checkbox to pick them for a bulk change.
 */
export default function ServiceTable({
    services,
    selection,
    sort,
    onSortChange,
}: {
    services: ServiceListItem[];
    selection: TicketSelection;
    sort: DataTableSort;
    onSortChange: (sort: DataTableSort) => void;
}) {
    const rowIds = services.map((service) => service.id);
    const rowKey = rowIds.join(',');
    const columns = useMemo(
        () => buildColumns(selection, rowIds),
        [selection, rowKey], // eslint-disable-line react-hooks/exhaustive-deps
    );

    return (
        <DataTable
            columns={columns}
            data={services}
            getRowId={(service) => service.id}
            sort={sort}
            onSortChange={onSortChange}
            isRowSelected={(service) => selection.isSelected(service.id)}
            onRowClick={(service) => router.visit(show.url(service.id))}
        />
    );
}
