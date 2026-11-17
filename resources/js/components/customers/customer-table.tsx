import { router } from '@inertiajs/react';
import {
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import { useMemo } from 'react';
import {
    BillingTypeBadge,
    PrefixCode,
} from '@/components/customers/customer-meta';
import DataTable, { dataTableColumns } from '@/components/data-table';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { ChoiceTag, useChoice } from '@/components/tickets/ticket-meta';
import { timeAgo } from '@/lib/format';
import { formatCents } from '@/lib/money';
import { show } from '@/routes/customers';
import type { CustomField, CustomerListItem } from '@/types';

const column = dataTableColumns<CustomerListItem>();

function CustomerType({ typeId }: { typeId: string | null }) {
    const type = useChoice('customer_type', typeId);

    return type ? (
        <ChoiceTag choice={type} className="text-soft-foreground" />
    ) : (
        <span className="text-subtle-foreground">—</span>
    );
}

function buildColumns(
    selection?: TicketSelection,
    rowIds: string[] = [],
    customFields: CustomField[] = [],
) {
    const pickedCount = rowIds.filter((id) => selection?.isSelected(id)).length;

    return column.columns([
        ...(selection
            ? [
                  column.display({
                      id: 'select',
                      meta: { className: 'w-9 pr-0' },
                      header: () => (
                          <SelectCheckbox
                              label="Select all customers on this page"
                              checked={
                                  rowIds.length > 0 &&
                                  pickedCount === rowIds.length
                              }
                              indeterminate={pickedCount > 0}
                              onChange={(isOn) =>
                                  selection.setMany(rowIds, isOn)
                              }
                          />
                      ),
                      cell: (info) => (
                          <SelectCheckbox
                              label={`Select ${info.row.original.name}`}
                              checked={selection.isSelected(
                                  info.row.original.id,
                              )}
                              onChange={() =>
                                  selection.toggle(info.row.original.id)
                              }
                          />
                      ),
                  }),
              ]
            : []),
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
                    <PrefixCode prefix={info.row.original.prefix} />
                    <span className="truncate font-medium">
                        {info.getValue()}
                    </span>
                    {info.row.original.is_sample && (
                        <SoftBadge tone="neutral">Sample</SoftBadge>
                    )}
                </span>
            ),
        }),
        column.accessor('type_id', {
            header: 'Type',
            meta: { className: 'hidden md:table-cell w-36' },
            cell: (info) => <CustomerType typeId={info.getValue()} />,
        }),
        column.accessor('billing_type', {
            header: 'Billing',
            meta: { className: 'hidden lg:table-cell w-28' },
            cell: (info) => <BillingTypeBadge billingType={info.getValue()} />,
        }),
        column.accessor('services_count', {
            header: 'Services',
            meta: { className: 'hidden lg:table-cell w-20 text-right' },
            cell: (info) => (
                <span className="text-muted-foreground tabular-nums">
                    {info.getValue()}
                </span>
            ),
        }),
        column.accessor('balance_in_cents', {
            header: 'Balance due',
            meta: { className: 'w-28 text-right' },
            cell: (info) =>
                info.getValue() === 0 ? (
                    <span className="text-subtle-foreground">—</span>
                ) : (
                    <span className="font-medium tabular-nums">
                        {formatCents(info.getValue())}
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
        column.accessor('updated_at', {
            header: 'Updated',
            meta: { className: 'hidden xl:table-cell w-28' },
            cell: (info) => (
                <span className="text-muted-foreground">
                    {timeAgo(info.getValue())}
                </span>
            ),
        }),
        ...customFields.map((field) =>
            column.accessor((customer) => customer.custom_fields[field.id], {
                id: `cf:${field.id}`,
                header: field.name,
                meta: { className: 'w-36' },
                cell: (info) =>
                    hasCustomFieldValue(field, info.getValue()) ? (
                        <CustomFieldValueView
                            field={field}
                            value={info.getValue()}
                            labels={info.row.original.custom_field_labels}
                        />
                    ) : (
                        <span className="text-subtle-foreground">—</span>
                    ),
            }),
        ),
    ]);
}

export default function CustomerTable({
    customers,
    selection,
    customFields = [],
}: {
    customers: CustomerListItem[];
    /** Custom fields to add as columns, after the built-in ones. */
    customFields?: CustomField[];
    /** Adds a checkbox column for picking customers for a bulk change. */
    selection?: TicketSelection;
}) {
    const rowIds = customers.map((customer) => customer.id);
    const rowKey = rowIds.join(',');
    const columns = useMemo(
        () => buildColumns(selection, rowIds, customFields),
        [selection, rowKey, customFields], // eslint-disable-line react-hooks/exhaustive-deps
    );

    return (
        <DataTable
            columns={columns}
            data={customers}
            getRowId={(customer) => customer.id}
            isRowSelected={(customer) =>
                selection?.isSelected(customer.id) ?? false
            }
            onRowClick={(customer) => router.visit(show.url(customer.id))}
        />
    );
}
