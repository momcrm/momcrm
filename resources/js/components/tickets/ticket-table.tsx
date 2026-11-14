import DataTable, {
    dataTableColumns,
    withoutColumn,
} from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import {
    AssigneeAvatar,
    DueChip,
    LabelChips,
    PriorityIcon,
    ProjectTag,
    TicketStatusPill,
    priorityOf,
} from '@/components/tickets/ticket-meta';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { cn } from '@/lib/utils';
import type { TicketListItem } from '@/types';

const column = dataTableColumns<TicketListItem>();

const columns = column.columns([
    column.accessor('number', {
        header: '#',
        enableSorting: true,
        meta: { className: 'w-16' },
        cell: (info) => (
            <span className="font-mono text-xs text-subtle-foreground">
                #{info.getValue()}
            </span>
        ),
    }),
    column.accessor('title', {
        header: 'Title',
        enableSorting: true,
        cell: (info) => (
            <span className="flex min-w-0 items-center gap-2">
                <span
                    className={cn(
                        'truncate font-medium',
                        info.row.original.status === 'closed' &&
                            'text-muted-foreground line-through',
                    )}
                >
                    {info.getValue()}
                </span>
                <LabelChips
                    labels={info.row.original.labels}
                    className="hidden flex-nowrap xl:flex"
                />
            </span>
        ),
    }),
    column.accessor('status', {
        header: 'Status',
        enableSorting: true,
        meta: { className: 'w-44' },
        cell: (info) => <TicketStatusPill status={info.getValue()} />,
    }),
    column.accessor('priority', {
        header: 'Priority',
        enableSorting: true,
        meta: { className: 'w-28' },
        cell: (info) => (
            <span className="flex items-center gap-1.5 text-muted-foreground">
                <PriorityIcon priority={info.getValue()} />
                {priorityOf(info.getValue()).label}
            </span>
        ),
    }),
    column.display({
        id: 'project',
        header: 'Project / customer',
        meta: { className: 'hidden w-52 lg:table-cell' },
        cell: (info) =>
            info.row.original.project_name ? (
                <ProjectTag
                    name={info.row.original.project_name}
                    color={info.row.original.project_color}
                />
            ) : (
                <span className="truncate text-xs text-muted-foreground">
                    {info.row.original.customer_name ?? '—'}
                </span>
            ),
    }),
    column.accessor('assignee_name', {
        header: 'Assignee',
        meta: { className: 'hidden w-40 md:table-cell' },
        cell: (info) => (
            <span className="flex items-center gap-2 truncate">
                <AssigneeAvatar name={info.getValue()} />
                <span className="truncate text-muted-foreground">
                    {info.getValue() ?? 'Unassigned'}
                </span>
            </span>
        ),
    }),
    column.accessor('due_on', {
        header: 'Due',
        enableSorting: true,
        meta: { className: 'w-28' },
        cell: (info) =>
            info.getValue() ? (
                <DueChip
                    dueOn={info.getValue()}
                    isClosed={info.row.original.status === 'closed'}
                />
            ) : (
                <span className="text-subtle-foreground">—</span>
            ),
    }),
]);

/**
 * The dense ticket table. Sorting reloads from the server when the page
 * handles it; rows open the drawer unless `onOpen` says otherwise.
 */
export default function TicketTable({
    tickets,
    sort,
    onSortChange,
    onOpen,
    hideProject = false,
}: {
    tickets: TicketListItem[];
    sort?: DataTableSort;
    onSortChange?: (sort: DataTableSort) => void;
    onOpen?: (ticket: TicketListItem) => void;
    hideProject?: boolean;
}) {
    const { openTicket } = useTicketActions();

    return (
        <DataTable
            columns={hideProject ? withoutColumn(columns, 'project') : columns}
            data={tickets}
            getRowId={(ticket) => ticket.id}
            sort={sort}
            onSortChange={onSortChange}
            onRowClick={(ticket) =>
                onOpen ? onOpen(ticket) : openTicket(ticket.id)
            }
        />
    );
}
