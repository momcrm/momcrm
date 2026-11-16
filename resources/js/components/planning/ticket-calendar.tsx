import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { DragEvent } from 'react';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import {
    AssigneeAvatar,
    PriorityGlyph,
    useIsClosedStatus,
    usePriority,
} from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { hueStyle } from '@/lib/colors';
import { addDays, addMonths, eachDay, parseDay, today } from '@/lib/dates';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import type { CalendarWindow, ProjectOption, TicketListItem } from '@/types';

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const monthFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
});
const shortFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});
const MONTH_CHIPS = 3;
const ONLY = ['tickets', 'calendar', 'drawer'];

type DragProps = {
    isDragging: boolean;
    onDragStart: () => void;
    onDragEnd: () => void;
};

/**
 * Tickets on their due dates in a Monday-first month grid or a week of day
 * columns. Clicking a ticket opens it; dragging it to another day reschedules
 * it. On the Calendar page it runs edge to edge (`fullBleed`); inside a project
 * it sits in a card.
 */
export default function TicketCalendar({
    calendar,
    tickets,
    projects,
    favoriteIds,
    showMine = true,
    fullBleed = false,
    projectId,
}: {
    calendar: CalendarWindow;
    tickets: TicketListItem[];
    projects: ProjectOption[];
    favoriteIds: string[];
    showMine?: boolean;
    fullBleed?: boolean;
    /** The project new tickets from this calendar belong to, if any. */
    projectId?: string;
}) {
    const { openTicket, updateTicket } = useTicketActions();
    const { openCreateTicket } = useCreateTicket();
    const [items, setItems] = useState(tickets);
    const [dragging, setDragging] = useState<string | null>(null);
    const [over, setOver] = useState<string | null>(null);
    const [expanded, setExpanded] = useState<string | null>(null);

    useEffect(() => setItems(tickets), [tickets]);

    const isMonth = calendar.view === 'month';
    const month = parseDay(calendar.date).getMonth();
    const days = eachDay(calendar.from, calendar.to);
    const now = today();
    const byDay = items.reduce<Record<string, TicketListItem[]>>(
        (groups, ticket) => {
            if (ticket.due_on) {
                (groups[ticket.due_on] ??= []).push(ticket);
            }

            return groups;
        },
        {},
    );
    const usedProjects = projects.filter((project) =>
        items.some((ticket) => ticket.project_id === project.id),
    );

    const go = (date: string, view = calendar.view) =>
        visitWithQuery(
            { date, calendar: view === 'month' ? null : view },
            { only: ONLY },
        );
    const step = (direction: number) =>
        go(
            isMonth
                ? addMonths(calendar.date, direction)
                : addDays(calendar.date, 7 * direction),
        );

    const drop = (day: string) => {
        const ticket = items.find((item) => item.id === dragging);

        setDragging(null);
        setOver(null);

        if (ticket && ticket.due_on !== day) {
            setItems(
                items.map((item) =>
                    item.id === ticket.id ? { ...item, due_on: day } : item,
                ),
            );
            updateTicket(ticket.id, { due_on: day });
        }
    };

    const dropTarget = (day: string) => ({
        onDragOver: (event: DragEvent) => {
            if (dragging) {
                event.preventDefault();
                setOver(day);
            }
        },
        onDrop: (event: DragEvent) => {
            event.preventDefault();
            drop(day);
        },
    });

    const dragProps = (ticket: TicketListItem): DragProps => ({
        isDragging: dragging === ticket.id,
        onDragStart: () => setDragging(ticket.id),
        onDragEnd: () => {
            setDragging(null);
            setOver(null);
        },
    });

    const title = isMonth
        ? monthFormatter.format(parseDay(calendar.date))
        : `${shortFormatter.format(parseDay(calendar.from))} – ${shortFormatter.format(parseDay(calendar.to))}`;

    return (
        <div className={cn('flex flex-col', fullBleed ? 'flex-1' : 'gap-3')}>
            <div
                className={cn(
                    'flex flex-wrap items-center gap-2',
                    fullBleed && 'border-t px-4 py-2 md:px-6',
                )}
            >
                <Button variant="outline" size="sm" onClick={() => go(now)}>
                    Today
                </Button>
                <div className="flex items-center">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Previous"
                        onClick={() => step(-1)}
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Next"
                        onClick={() => step(1)}
                    >
                        <ChevronRight />
                    </Button>
                </div>
                <h2 className="mr-2 text-[15px] font-semibold">{title}</h2>
                {showMine && (
                    <label className="flex items-center gap-2 text-ui text-soft-foreground">
                        <Checkbox
                            checked={calendar.mine}
                            onCheckedChange={(checked) =>
                                visitWithQuery(
                                    { mine: checked === true ? '1' : null },
                                    { only: ONLY },
                                )
                            }
                        />
                        Only my tickets
                    </label>
                )}
                <div className="ml-auto flex flex-wrap items-center gap-x-4 gap-y-2">
                    {usedProjects.length > 0 && (
                        <ul className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            {usedProjects.map((project) => (
                                <li
                                    key={project.id}
                                    className="flex items-center gap-1.5"
                                >
                                    <span
                                        className={cn(
                                            'hue-dot',
                                            'size-2 rounded-full',
                                        )}
                                        style={hueStyle(project.hue)}
                                    />
                                    {project.name}
                                </li>
                            ))}
                        </ul>
                    )}
                    <ToggleGroup
                        type="single"
                        variant="segmented"
                        value={calendar.view}
                        onValueChange={(view) =>
                            view &&
                            go(calendar.date, view as CalendarWindow['view'])
                        }
                        aria-label="Calendar view"
                    >
                        <ToggleGroupItem value="month">Month</ToggleGroupItem>
                        <ToggleGroupItem value="week">Week</ToggleGroupItem>
                    </ToggleGroup>
                </div>
            </div>

            <div
                className={cn(
                    'flex flex-col',
                    fullBleed
                        ? 'flex-1 border-t'
                        : 'overflow-hidden rounded-lg border bg-card shadow-xs',
                )}
            >
                {isMonth ? (
                    <>
                        <div className="grid grid-cols-7 border-b">
                            {weekdays.map((weekday) => (
                                <div
                                    key={weekday}
                                    className="px-2.5 py-2 text-xs font-medium text-muted-foreground"
                                >
                                    {weekday}
                                </div>
                            ))}
                        </div>
                        <div
                            className={cn(
                                'grid grid-cols-7',
                                fullBleed
                                    ? 'flex-1 auto-rows-[minmax(7rem,1fr)]'
                                    : 'auto-rows-[minmax(7rem,auto)]',
                            )}
                        >
                            {days.map((day) => {
                                const dayTickets = byDay[day] ?? [];
                                const isExpanded = expanded === day;
                                const shown = isExpanded
                                    ? dayTickets
                                    : dayTickets.slice(0, MONTH_CHIPS);

                                return (
                                    <div
                                        key={day}
                                        {...dropTarget(day)}
                                        className={cn(
                                            'flex min-w-0 flex-col gap-1 border-r border-b p-1.5 [&:nth-child(7n)]:border-r-0',
                                            parseDay(day).getMonth() !==
                                                month && 'bg-sunken',
                                            over === day &&
                                                'bg-primary/5 ring-2 ring-primary/30 ring-inset',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'flex size-[22px] items-center justify-center self-start rounded-full text-xs font-medium',
                                                day === now &&
                                                    'bg-primary text-primary-foreground',
                                            )}
                                        >
                                            {parseDay(day).getDate()}
                                        </span>
                                        {shown.map((ticket) => (
                                            <MonthChip
                                                key={ticket.id}
                                                ticket={ticket}
                                                isFavorite={favoriteIds.includes(
                                                    ticket.id,
                                                )}
                                                onOpen={() =>
                                                    openTicket(ticket.id)
                                                }
                                                {...dragProps(ticket)}
                                            />
                                        ))}
                                        {!isExpanded &&
                                            dayTickets.length > MONTH_CHIPS && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setExpanded(day)
                                                    }
                                                    className="px-1.5 text-left text-xs text-muted-foreground hover:text-foreground"
                                                >
                                                    +
                                                    {dayTickets.length -
                                                        MONTH_CHIPS}{' '}
                                                    more
                                                </button>
                                            )}
                                    </div>
                                );
                            })}
                        </div>
                    </>
                ) : (
                    <div
                        className={cn(
                            'grid grid-cols-7',
                            fullBleed ? 'flex-1' : 'min-h-96',
                        )}
                    >
                        {days.map((day, index) => {
                            const dayTickets = byDay[day] ?? [];
                            const isToday = day === now;

                            return (
                                <div
                                    key={day}
                                    {...dropTarget(day)}
                                    className={cn(
                                        'flex min-w-0 flex-col border-r last:border-r-0',
                                        over === day &&
                                            'bg-primary/5 ring-2 ring-primary/30 ring-inset',
                                    )}
                                >
                                    <div className="flex h-10 items-center gap-1.5 border-b pr-1.5 pl-2.5">
                                        <span
                                            className={cn(
                                                'text-[15px] font-semibold tabular-nums',
                                                isToday && 'text-primary',
                                            )}
                                        >
                                            {parseDay(day).getDate()}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {weekdays[index]}
                                        </span>
                                        <Button
                                            variant="ghost"
                                            size="icon-xs"
                                            className="ml-auto"
                                            aria-label={`New ticket due ${shortFormatter.format(parseDay(day))}`}
                                            onClick={() =>
                                                openCreateTicket({
                                                    dueOn: day,
                                                    projectId,
                                                })
                                            }
                                        >
                                            <Plus />
                                        </Button>
                                    </div>
                                    <div className="flex flex-1 flex-col gap-1.5 p-1.5">
                                        {dayTickets.length === 0 ? (
                                            <span className="px-1 py-0.5 text-xs text-subtle-foreground">
                                                No tickets
                                            </span>
                                        ) : (
                                            dayTickets.map((ticket) => (
                                                <WeekCard
                                                    key={ticket.id}
                                                    ticket={ticket}
                                                    isFavorite={favoriteIds.includes(
                                                        ticket.id,
                                                    )}
                                                    onOpen={() =>
                                                        openTicket(ticket.id)
                                                    }
                                                    {...dragProps(ticket)}
                                                />
                                            ))
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}

/**
 * A ticket in the month grid: tinted in its project's colour, with its
 * priority and assignee. Done tickets stay, struck through.
 */
function MonthChip({
    ticket,
    isFavorite,
    onOpen,
    isDragging,
    onDragStart,
    onDragEnd,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    onOpen: () => void;
} & DragProps) {
    const isClosed = useIsClosedStatus()(ticket.status_id);
    const priority = usePriority(ticket.priority_id);

    return (
        <button
            type="button"
            draggable
            onDragStart={(event) => {
                event.dataTransfer.effectAllowed = 'move';
                onDragStart();
            }}
            onDragEnd={onDragEnd}
            onClick={onOpen}
            title={`#${ticket.number} ${ticket.title}`}
            className={cn(
                'flex h-[22px] w-full min-w-0 shrink-0 items-center gap-1.5 rounded-[5px] border px-1.5 text-left text-xs transition-shadow hover:shadow-xs',
                ticket.project_id ? 'hue-tint' : 'border-border bg-muted',
                isClosed && 'text-muted-foreground',
                isDragging && 'opacity-40',
            )}
            style={ticket.project_id ? hueStyle(ticket.project_hue) : undefined}
        >
            <PriorityGlyph priority={priority} className="size-3" />
            <span className={cn('flex-1 truncate', isClosed && 'line-through')}>
                {isFavorite && '★ '}
                {ticket.title}
            </span>
            {ticket.assignee_name && (
                <AssigneeAvatar
                    name={ticket.assignee_name}
                    className="size-4 text-[7px]"
                />
            )}
        </button>
    );
}

/**
 * A ticket in a week column: project dot and title, then its priority,
 * project and assignee.
 */
function WeekCard({
    ticket,
    isFavorite,
    onOpen,
    isDragging,
    onDragStart,
    onDragEnd,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    onOpen: () => void;
} & DragProps) {
    const isClosed = useIsClosedStatus()(ticket.status_id);
    const priority = usePriority(ticket.priority_id);

    return (
        <button
            type="button"
            draggable
            onDragStart={(event) => {
                event.dataTransfer.effectAllowed = 'move';
                onDragStart();
            }}
            onDragEnd={onDragEnd}
            onClick={onOpen}
            className={cn(
                'flex w-full min-w-0 flex-col gap-1.5 rounded-md border bg-card p-2 text-left shadow-xs transition-[border-color,box-shadow] hover:border-border-strong',
                isDragging && 'opacity-40',
            )}
        >
            <span className="flex min-w-0 items-start gap-1.5">
                <span
                    className={cn(
                        'mt-1.5 size-2 shrink-0 rounded-full',
                        ticket.project_id ? 'hue-dot' : 'bg-border-strong',
                    )}
                    style={
                        ticket.project_id
                            ? hueStyle(ticket.project_hue)
                            : undefined
                    }
                />
                <span
                    className={cn(
                        'line-clamp-2 text-ui font-medium',
                        isClosed && 'text-muted-foreground line-through',
                    )}
                >
                    {isFavorite && '★ '}
                    {ticket.title}
                </span>
            </span>
            <span className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
                <PriorityGlyph priority={priority} className="size-3" />
                <span className="flex-1 truncate">
                    {ticket.project_name ?? `#${ticket.number}`}
                </span>
                {ticket.assignee_name && (
                    <AssigneeAvatar
                        name={ticket.assignee_name}
                        className="size-4 text-[7px]"
                    />
                )}
            </span>
        </button>
    );
}
