import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import { LabelChips } from '@/components/labels';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { useTicketSections } from '@/components/tickets/ticket-grouping';
import {
    AssigneeAvatar,
    ChoiceTag,
    DueChip,
    formatEstimate,
    PriorityGlyph,
    ProjectTag,
    StatusGlyph,
    TicketStatusLabel,
    useChoice,
    useIsClosedStatus,
    usePriority,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import type {
    TicketColumnKey,
    TicketGroupBy,
} from '@/hooks/use-ticket-view-prefs';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { cn } from '@/lib/utils';
import type { TicketListItem } from '@/types';

const column = dataTableColumns<TicketListItem>();

/**
 * The columns a viewer can show or hide, in order.
 */
export const ticketTableColumnChoices: {
    key: TicketColumnKey;
    label: string;
}[] = [
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'due_on', label: 'Due date' },
    { key: 'labels', label: 'Labels' },
    { key: 'estimate', label: 'Estimate' },
    { key: 'project', label: 'Project' },
    { key: 'type', label: 'Type' },
];

const columns = column.columns([
    column.accessor('title', {
        header: 'Task',
        enableSorting: true,
        meta: { className: 'w-auto min-w-[260px]' },
        cell: (info) => <TitleCell ticket={info.row.original} />,
    }),
    column.accessor('status_id', {
        id: 'status',
        header: 'Status',
        enableSorting: true,
        meta: { className: 'w-[122px]' },
        cell: (info) => (
            <TicketStatusLabel
                statusId={info.getValue()}
                className="max-w-full text-foreground"
            />
        ),
    }),
    column.accessor('priority_id', {
        id: 'priority',
        header: 'Priority',
        enableSorting: true,
        meta: { className: 'w-[98px]' },
        cell: (info) => <PriorityCell priorityId={info.getValue()} />,
    }),
    column.accessor('assignee_name', {
        id: 'assignee',
        header: 'Assignee',
        meta: { className: 'w-[136px]' },
        cell: (info) => (
            <span className="flex min-w-0 items-center gap-1.5">
                <AssigneeAvatar name={info.getValue()} />
                <span
                    className={cn(
                        'truncate',
                        !info.getValue() && 'text-subtle-foreground',
                    )}
                >
                    {info.getValue() ?? 'Unassigned'}
                </span>
            </span>
        ),
    }),
    column.accessor('due_on', {
        header: 'Due date',
        enableSorting: true,
        meta: { className: 'w-[98px]' },
        cell: (info) => <DueCell ticket={info.row.original} />,
    }),
    column.display({
        id: 'labels',
        header: 'Labels',
        meta: { className: 'w-[158px]' },
        cell: (info) =>
            info.row.original.labels.length ? (
                <LabelChips
                    labels={info.row.original.labels}
                    className="flex-nowrap overflow-hidden"
                />
            ) : (
                <span className="text-subtle-foreground">—</span>
            ),
    }),
    column.accessor('estimate_minutes', {
        id: 'estimate',
        header: 'Estimate',
        meta: { className: 'w-[80px]' },
        cell: (info) =>
            info.getValue() ? (
                <span className="tabular-nums">
                    {formatEstimate(info.getValue() as number)}
                </span>
            ) : (
                <span className="text-subtle-foreground">—</span>
            ),
    }),
    column.display({
        id: 'project',
        header: 'Project',
        meta: { className: 'w-[150px]' },
        cell: (info) =>
            info.row.original.project_name ? (
                <ProjectTag
                    name={info.row.original.project_name}
                    hue={info.row.original.project_hue}
                    className="text-[12.5px] text-foreground"
                />
            ) : (
                <span className="truncate text-muted-foreground">
                    {info.row.original.customer_name ?? '—'}
                </span>
            ),
    }),
    column.accessor('type_id', {
        id: 'type',
        header: 'Type',
        meta: { className: 'w-[120px]' },
        cell: (info) => <TypeCell typeId={info.getValue()} />,
    }),
]);

/**
 * The ticket table, as in gr8r: a status glyph, title and number, then a
 * ruled column per field. On the tickets page it runs edge to edge in
 * sections; elsewhere it sits in a card. Sorting reloads from the server
 * when the page handles it; rows open the drawer unless `onOpen` says
 * otherwise.
 */
export default function TicketTable({
    tickets,
    sort,
    onSortChange,
    onOpen,
    hideProject = false,
    flush = false,
    groupBy = 'none',
    hiddenColumns = ['type'],
    selection,
}: {
    tickets: TicketListItem[];
    sort?: DataTableSort;
    onSortChange?: (sort: DataTableSort) => void;
    onOpen?: (ticket: TicketListItem) => void;
    hideProject?: boolean;
    flush?: boolean;
    groupBy?: TicketGroupBy;
    hiddenColumns?: TicketColumnKey[];
    /** Adds a checkbox column for picking tickets to change together. */
    selection?: TicketSelection;
}) {
    const { openTicket } = useTicketActions();
    const sections = useTicketSections(tickets, groupBy);
    const hidden: string[] = [
        ...hiddenColumns,
        ...(hideProject ? ['project'] : []),
    ];
    const allIds = tickets.map((ticket) => ticket.id);
    const pickedCount = selection?.ids.length ?? 0;
    const shownColumns = [
        ...(selection
            ? [
                  column.display({
                      id: 'select',
                      header: () => (
                          <SelectCheckbox
                              label="Select all tickets"
                              checked={
                                  pickedCount > 0 &&
                                  pickedCount === allIds.length
                              }
                              indeterminate={pickedCount > 0}
                              onChange={(isOn) =>
                                  selection.setMany(allIds, isOn)
                              }
                          />
                      ),
                      meta: { className: 'w-[44px] md:w-[52px]' },
                      cell: (info) => (
                          <SelectCheckbox
                              label={`Select #${info.row.original.number}`}
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
        ...columns.filter((item) => !hidden.includes(item.id ?? '')),
    ];
    const sectionOf = new Map(
        sections.flatMap((section) =>
            section.tickets.map((ticket) => [ticket.id, section.key] as const),
        ),
    );

    return (
        <DataTable
            columns={shownColumns}
            data={tickets}
            getRowId={(ticket) => ticket.id}
            sort={sort}
            onSortChange={onSortChange}
            variant={flush ? 'flush' : 'card'}
            groups={
                groupBy === 'none'
                    ? undefined
                    : sections.map((section) => ({
                          key: section.key,
                          header: (
                              <span className="flex items-center gap-2">
                                  {section.icon}
                                  {section.label}
                                  <span className="font-medium text-muted-foreground">
                                      {section.tickets.length}
                                  </span>
                              </span>
                          ),
                      }))
            }
            getGroupKey={(ticket) => sectionOf.get(ticket.id) ?? ''}
            isRowSelected={
                selection
                    ? (ticket) => selection.isSelected(ticket.id)
                    : undefined
            }
            onRowClick={(ticket) =>
                onOpen ? onOpen(ticket) : openTicket(ticket.id)
            }
        />
    );
}

function PriorityCell({ priorityId }: { priorityId: string | null }) {
    const priority = usePriority(priorityId);

    return (
        <span className="flex items-center gap-1.5">
            <PriorityGlyph priority={priority} />
            <span className={cn(!priority && 'text-subtle-foreground')}>
                {priority?.name ?? 'None'}
            </span>
        </span>
    );
}

function TypeCell({ typeId }: { typeId: string | null }) {
    const type = useChoice('ticket_type', typeId);

    return type ? (
        <ChoiceTag choice={type} className="truncate" />
    ) : (
        <span className="text-subtle-foreground">—</span>
    );
}

function TitleCell({ ticket }: { ticket: TicketListItem }) {
    const status = useTicketStatus(ticket.status_id);
    const isClosed = status?.category === 'closed';

    return (
        <span className="flex min-w-0 items-center gap-2">
            <StatusGlyph status={status} />
            <span
                className={cn(
                    'min-w-0 flex-1 truncate font-medium',
                    isClosed && 'text-muted-foreground line-through',
                )}
            >
                {ticket.title}
            </span>
            <span className="shrink-0 font-mono text-[11px] text-muted-foreground">
                #{ticket.number}
            </span>
        </span>
    );
}

function DueCell({ ticket }: { ticket: TicketListItem }) {
    const isClosed = useIsClosedStatus()(ticket.status_id);

    return ticket.due_on ? (
        <DueChip
            dueOn={ticket.due_on}
            isClosed={isClosed}
            showIcon={false}
            className="text-[12.5px]"
        />
    ) : (
        <span className="text-subtle-foreground">—</span>
    );
}
