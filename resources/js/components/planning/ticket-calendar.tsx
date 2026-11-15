import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useIsClosedStatus } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { hueStyle } from '@/lib/colors';
import {
    addDays,
    addMonths,
    eachDay,
    isWeekend,
    parseDay,
    today,
} from '@/lib/dates';
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

/**
 * Tickets on their due dates in a Monday-first month or week. Clicking a chip
 * opens the ticket; dragging it to another day reschedules it.
 */
export default function TicketCalendar({
    calendar,
    tickets,
    projects,
    favoriteIds,
    showMine = true,
}: {
    calendar: CalendarWindow;
    tickets: TicketListItem[];
    projects: ProjectOption[];
    favoriteIds: string[];
    showMine?: boolean;
}) {
    const isClosed = useIsClosedStatus();
    const { openTicket, updateTicket } = useTicketActions();
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

    const title = isMonth
        ? monthFormatter.format(parseDay(calendar.date))
        : `${shortFormatter.format(parseDay(calendar.from))} – ${shortFormatter.format(parseDay(calendar.to))}`;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                <h2 className="mr-2 text-lg font-semibold">{title}</h2>
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Previous"
                        onClick={() => step(-1)}
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8"
                        onClick={() => go(now)}
                    >
                        Today
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Next"
                        onClick={() => step(1)}
                    >
                        <ChevronRight />
                    </Button>
                </div>
                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={calendar.view}
                    onValueChange={(view) =>
                        view &&
                        go(calendar.date, view as CalendarWindow['view'])
                    }
                    className="bg-card"
                    aria-label="Calendar view"
                >
                    <ToggleGroupItem value="month" className="px-3">
                        Month
                    </ToggleGroupItem>
                    <ToggleGroupItem value="week" className="px-3">
                        Week
                    </ToggleGroupItem>
                </ToggleGroup>
                {showMine && (
                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
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
                {usedProjects.length > 0 && (
                    <ul className="ml-auto flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {usedProjects.map((project) => (
                            <li
                                key={project.id}
                                className="flex items-center gap-1.5"
                            >
                                <span
                                    className={cn(
                                        'hue-dot',
                                        'size-2 rounded-[3px]',
                                    )}
                                    style={hueStyle(project.hue)}
                                />
                                {project.name}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="overflow-hidden rounded-lg border bg-card shadow-xs">
                <div className="grid grid-cols-7 border-b bg-muted/60">
                    {weekdays.map((weekday) => (
                        <div
                            key={weekday}
                            className="px-2 py-1.5 text-xs font-medium text-muted-foreground"
                        >
                            {weekday}
                        </div>
                    ))}
                </div>
                <div className="grid grid-cols-7">
                    {days.map((day) => {
                        const dayTickets = byDay[day] ?? [];
                        const isExpanded = expanded === day || !isMonth;
                        const shown = isExpanded
                            ? dayTickets
                            : dayTickets.slice(0, MONTH_CHIPS);
                        const outside =
                            isMonth && parseDay(day).getMonth() !== month;

                        return (
                            <div
                                key={day}
                                onDragOver={(event) => {
                                    if (dragging) {
                                        event.preventDefault();
                                        setOver(day);
                                    }
                                }}
                                onDrop={(event) => {
                                    event.preventDefault();
                                    drop(day);
                                }}
                                className={cn(
                                    'flex flex-col gap-1 border-r border-b p-1.5 [&:nth-child(7n)]:border-r-0',
                                    isMonth ? 'min-h-28' : 'min-h-[60vh]',
                                    isWeekend(day) && 'bg-muted/70',
                                    over === day &&
                                        'bg-primary/5 ring-2 ring-primary/30 ring-inset',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-6 items-center justify-center self-start rounded-full text-xs',
                                        day === now &&
                                            'bg-primary font-semibold text-primary-foreground',
                                        outside &&
                                            day !== now &&
                                            'text-subtle-foreground',
                                    )}
                                >
                                    {parseDay(day).getDate()}
                                </span>
                                {shown.map((ticket) => (
                                    <button
                                        key={ticket.id}
                                        type="button"
                                        draggable
                                        onDragStart={(event) => {
                                            event.dataTransfer.effectAllowed =
                                                'move';
                                            setDragging(ticket.id);
                                        }}
                                        onDragEnd={() => {
                                            setDragging(null);
                                            setOver(null);
                                        }}
                                        onClick={() => openTicket(ticket.id)}
                                        title={`#${ticket.number} ${ticket.title}`}
                                        className={cn(
                                            'flex items-center gap-1.5 truncate rounded-[5px] border bg-card px-1.5 py-0.5 text-left text-xs shadow-xs hover:border-border-strong',
                                            isClosed(ticket.status_id) &&
                                                'text-muted-foreground line-through',
                                            dragging === ticket.id &&
                                                'opacity-40',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'h-3 w-1 shrink-0 rounded-full',
                                                ticket.project_id
                                                    ? 'hue-dot'
                                                    : 'bg-border-strong',
                                            )}
                                            style={
                                                ticket.project_id
                                                    ? hueStyle(
                                                          ticket.project_hue,
                                                      )
                                                    : undefined
                                            }
                                        />
                                        <span className="truncate">
                                            {favoriteIds.includes(ticket.id) &&
                                                '★ '}
                                            {ticket.title}
                                        </span>
                                    </button>
                                ))}
                                {!isExpanded &&
                                    dayTickets.length > MONTH_CHIPS && (
                                        <button
                                            type="button"
                                            onClick={() => setExpanded(day)}
                                            className="px-1.5 text-left text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            +{dayTickets.length - MONTH_CHIPS}{' '}
                                            more
                                        </button>
                                    )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
