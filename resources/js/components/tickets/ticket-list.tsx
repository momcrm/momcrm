import {
    CalendarDays,
    ChevronDown,
    MoreHorizontal,
    Plus,
    Star,
} from 'lucide-react';
import { useState } from 'react';
import QuickAddTicket from '@/components/tickets/quick-add-ticket';
import { SelectCheckbox } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { useTicketSections } from '@/components/tickets/ticket-grouping';
import type { TicketSection } from '@/components/tickets/ticket-grouping';
import {
    AssigneeAvatar,
    DueChip,
    PriorityGlyph,
    TicketStatusLabel,
    useIsClosedStatus,
    usePriority,
} from '@/components/tickets/ticket-meta';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { TicketGroupBy } from '@/hooks/use-ticket-view-prefs';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { TicketListItem, TicketPickerOptions } from '@/types';

const GRID =
    'md:grid md:grid-cols-[minmax(0,1fr)_112px_136px_92px_92px_150px_32px]';

/** The same columns behind a checkbox, when tickets can be picked. */
const SELECTABLE_GRID =
    'md:grid md:grid-cols-[26px_minmax(0,1fr)_112px_136px_92px_92px_150px_32px]';

/**
 * The tickets page's list, as in gr8r: a header of columns, then a section
 * per status (or priority, assignee or project) that folds away, with a row
 * per ticket and a place to add one.
 */
export default function TicketList({
    tickets,
    favoriteIds,
    options,
    groupBy,
    collapseClosed = false,
    projectId = null,
    selection,
}: {
    tickets: TicketListItem[];
    favoriteIds: string[];
    options: TicketPickerOptions;
    groupBy: TicketGroupBy;
    collapseClosed?: boolean;
    projectId?: string | null;
    /** Adds checkboxes for picking tickets to change together. */
    selection?: TicketSelection;
}) {
    const sections = useTicketSections(tickets, groupBy, { collapseClosed });
    const visible = sections.filter((section) => section.tickets.length > 0);
    const allIds = tickets.map((ticket) => ticket.id);
    const pickedCount = selection?.ids.length ?? 0;

    return (
        <div className="pb-20 text-[12.5px]">
            <div
                className={cn(
                    selection ? SELECTABLE_GRID : GRID,
                    'sticky top-0 z-[2] hidden min-h-8 items-center border-b bg-background px-4 text-xs font-medium text-muted-foreground md:px-6',
                )}
            >
                {selection && (
                    <span className="flex">
                        <SelectCheckbox
                            label="Select all tickets"
                            checked={
                                pickedCount > 0 && pickedCount === allIds.length
                            }
                            indeterminate={pickedCount > 0}
                            onChange={(isOn) => selection.setMany(allIds, isOn)}
                        />
                    </span>
                )}
                <span className={cn(!selection && 'pl-[22px]')}>Task</span>
                <span className="px-2">Status</span>
                <span className="px-2">Assignee</span>
                <span className="px-2">Priority</span>
                <span className="px-2">Due date</span>
                <span className="px-2">Project</span>
                <span />
            </div>
            {visible.map((section) => (
                <Section
                    key={section.key}
                    section={section}
                    favoriteIds={favoriteIds}
                    options={options}
                    projectId={projectId}
                    selection={selection}
                />
            ))}
        </div>
    );
}

function Section({
    section,
    favoriteIds,
    options,
    projectId,
    selection,
}: {
    section: TicketSection;
    favoriteIds: string[];
    options: TicketPickerOptions;
    projectId: string | null;
    selection?: TicketSelection;
}) {
    const [isCollapsed, setIsCollapsed] = useState(section.collapsed);
    const [isAdding, setIsAdding] = useState(false);
    const { openTicket } = useTicketActions();

    return (
        <section>
            <div className="group/grp mt-2.5 flex h-9 items-center gap-2 border-b px-4 font-semibold select-none md:px-6">
                <button
                    type="button"
                    aria-expanded={!isCollapsed}
                    aria-label={`${isCollapsed ? 'Show' : 'Hide'} ${section.label}`}
                    onClick={() => setIsCollapsed(!isCollapsed)}
                    className="-ml-1 flex size-[18px] items-center justify-center rounded text-subtle-foreground hover:bg-muted hover:text-foreground"
                >
                    <ChevronDown
                        className={cn(
                            'size-3.5 transition-transform',
                            isCollapsed && '-rotate-90',
                        )}
                    />
                </button>
                {section.icon}
                <span>{section.label}</span>
                <span className="font-medium text-muted-foreground">
                    {section.tickets.length}
                </span>
                {section.statusId && (
                    <button
                        type="button"
                        aria-label={`Add a ticket to ${section.label}`}
                        onClick={() => {
                            setIsCollapsed(false);
                            setIsAdding(true);
                        }}
                        className="flex size-[22px] items-center justify-center rounded-md text-soft-foreground opacity-60 group-hover/grp:opacity-100 hover:bg-muted hover:text-foreground"
                    >
                        <Plus className="size-3.5" />
                    </button>
                )}
            </div>
            {!isCollapsed && (
                <>
                    <ul>
                        {section.tickets.map((ticket) => (
                            <li key={ticket.id}>
                                <Row
                                    ticket={ticket}
                                    isFavorite={favoriteIds.includes(ticket.id)}
                                    onOpen={() => openTicket(ticket.id)}
                                    selection={selection}
                                />
                            </li>
                        ))}
                    </ul>
                    {section.statusId &&
                        (isAdding ? (
                            <div className="border-b border-divider px-4 py-2 md:px-6 md:pl-[46px]">
                                <div className="max-w-md">
                                    <QuickAddTicket
                                        statusId={section.statusId}
                                        options={options}
                                        projectId={projectId}
                                        onClose={() => setIsAdding(false)}
                                    />
                                </div>
                            </div>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setIsAdding(true)}
                                className="flex h-[34px] w-full items-center gap-2 border-b border-divider px-4 text-left text-muted-foreground hover:bg-muted hover:text-foreground md:pl-[26px]"
                            >
                                <Plus className="size-3.5" />
                                Add ticket
                            </button>
                        ))}
                </>
            )}
        </section>
    );
}

function Row({
    ticket,
    isFavorite,
    onOpen,
    selection,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    onOpen: () => void;
    selection?: TicketSelection;
}) {
    const isClosed = useIsClosedStatus()(ticket.status_id);
    const isPicked = selection?.isSelected(ticket.id) ?? false;
    const priority = usePriority(ticket.priority_id);
    const { toggleStar } = useTicketActions();

    return (
        <div
            role="button"
            tabIndex={0}
            onClick={onOpen}
            onKeyDown={(event) => {
                if (event.key === 'Enter') {
                    onOpen();
                }
            }}
            className={cn(
                selection ? SELECTABLE_GRID : GRID,
                'flex min-h-10 cursor-pointer items-center border-b border-divider px-4 transition-colors outline-none md:px-6',
                isPicked
                    ? 'bg-primary/10'
                    : 'hover:bg-muted focus-visible:bg-muted',
            )}
        >
            {selection && (
                <span className="mr-2 flex md:mr-0">
                    <SelectCheckbox
                        label={`Select #${ticket.number}`}
                        checked={isPicked}
                        onChange={() => selection.toggle(ticket.id)}
                    />
                </span>
            )}
            <span
                className={cn(
                    'flex min-w-0 flex-1 items-center gap-2 py-2 pr-2',
                    !selection && 'md:pl-[22px]',
                )}
            >
                <span className="shrink-0 font-mono text-[11px] text-muted-foreground">
                    #{ticket.number}
                </span>
                <span
                    className={cn(
                        'truncate text-[13.5px]',
                        isClosed &&
                            'text-muted-foreground line-through decoration-muted-foreground',
                    )}
                >
                    {ticket.title}
                </span>
                {isFavorite && (
                    <Star className="size-3 shrink-0 fill-amber-400 text-amber-400" />
                )}
                <AssigneeAvatar
                    name={ticket.assignee_name}
                    className="ml-auto md:hidden"
                />
            </span>
            <span className="hidden px-2 md:flex">
                <TicketStatusLabel statusId={ticket.status_id} />
            </span>
            <span className="hidden min-w-0 items-center gap-1.5 px-2 md:flex">
                <AssigneeAvatar name={ticket.assignee_name} />
                <span
                    className={cn(
                        'truncate',
                        !ticket.assignee_name && 'text-subtle-foreground',
                    )}
                >
                    {ticket.assignee_name ?? 'Unassigned'}
                </span>
            </span>
            <span className="hidden items-center gap-1.5 px-2 md:flex">
                <PriorityGlyph priority={priority} />
                <span className={cn(!priority && 'text-subtle-foreground')}>
                    {priority?.name ?? 'None'}
                </span>
            </span>
            <span className="hidden px-2 md:flex">
                {ticket.due_on ? (
                    <DueChip
                        dueOn={ticket.due_on}
                        isClosed={isClosed}
                        className="text-[12.5px]"
                    />
                ) : (
                    <span className="inline-flex items-center gap-1 text-subtle-foreground">
                        <CalendarDays className="size-3" />
                        Set date
                    </span>
                )}
            </span>
            <span className="hidden min-w-0 items-center gap-1.5 px-2 md:flex">
                {ticket.project_name ? (
                    <>
                        <span
                            aria-hidden
                            className="size-2 shrink-0 rounded-[3px] hue-dot"
                            style={hueStyle(ticket.project_hue)}
                        />
                        <span className="truncate">{ticket.project_name}</span>
                    </>
                ) : (
                    <span className="truncate text-muted-foreground">
                        {ticket.customer_name ?? '—'}
                    </span>
                )}
            </span>
            <span
                className="hidden justify-end md:flex"
                onClick={(event) => event.stopPropagation()}
            >
                <DropdownMenu>
                    <DropdownMenuTrigger
                        aria-label={`More for #${ticket.number}`}
                        className="flex size-6 items-center justify-center rounded-md text-soft-foreground outline-none hover:bg-accent hover:text-foreground data-[state=open]:bg-accent"
                    >
                        <MoreHorizontal className="size-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-44">
                        <DropdownMenuItem onSelect={onOpen}>
                            Open ticket
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => toggleStar(ticket.id)}
                        >
                            <Star />
                            {isFavorite
                                ? 'Remove from favorites'
                                : 'Add to favorites'}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </span>
        </div>
    );
}
