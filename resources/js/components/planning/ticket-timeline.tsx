import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { PointerEvent as ReactPointerEvent } from 'react';
import { ProjectIcon } from '@/components/projects/project-meta';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { colorDot } from '@/lib/colors';
import {
    addDays,
    addMonths,
    daysBetween,
    eachDay,
    isWeekend,
    parseDay,
    today,
} from '@/lib/dates';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import type {
    TicketListItem,
    TimelineMilestone,
    TimelineProject,
    TimelineWindow,
} from '@/types';

const LABEL_WIDTH = 240;
const ROW = 'h-9';
const ONLY = ['tickets', 'timeline', 'projects', 'milestones', 'drawer'];
const monthFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    year: 'numeric',
});
const dayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});

type Drag = {
    id: string;
    mode: 'move' | 'start' | 'end';
    originX: number;
    delta: number;
};

function spanOf(ticket: TicketListItem): [string, string] {
    const start = ticket.starts_on ?? ticket.due_on ?? '';
    const end = ticket.due_on ?? ticket.starts_on ?? '';

    return [start, end];
}

/**
 * A Gantt chart: dated tickets as bars grouped by project, each project's span
 * and milestones above its tickets, and a line for today. Drag a bar to move
 * it, or its ends to change the start or due date.
 */
export default function TicketTimeline({
    timeline,
    tickets,
    projects,
    milestones,
    showUngrouped = true,
}: {
    timeline: TimelineWindow;
    tickets: TicketListItem[];
    projects: TimelineProject[];
    milestones: TimelineMilestone[];
    showUngrouped?: boolean;
}) {
    const { openTicket, updateTicket } = useTicketActions();
    const [items, setItems] = useState(tickets);
    const [drag, setDrag] = useState<Drag | null>(null);
    const dragRef = useRef<Drag | null>(null);
    const moved = useRef(false);

    useEffect(() => setItems(tickets), [tickets]);

    const dayWidth = timeline.zoom === 'weeks' ? 28 : 6;
    const days = eachDay(timeline.from, timeline.to);
    const width = days.length * dayWidth;
    const now = today();
    const offset = (day: string) => daysBetween(timeline.from, day) * dayWidth;

    const groups = [
        ...projects.map((project) => ({
            project,
            tickets: items.filter((ticket) => ticket.project_id === project.id),
            milestones: milestones.filter(
                (milestone) => milestone.project_id === project.id,
            ),
        })),
        ...(showUngrouped
            ? [
                  {
                      project: null,
                      tickets: items.filter(
                          (ticket) => ticket.project_id === null,
                      ),
                      milestones: [],
                  },
              ]
            : []),
    ].filter(
        (group) =>
            group.tickets.length > 0 ||
            (group.project &&
                (group.project.starts_on ||
                    group.project.due_on ||
                    group.milestones.length > 0)),
    );

    const months = days.reduce<
        { label: string; start: string; count: number }[]
    >((list, day) => {
        const label = monthFormatter.format(parseDay(day));
        const last = list[list.length - 1];

        if (last && last.label === label) {
            last.count++;
        } else {
            list.push({ label, start: day, count: 1 });
        }

        return list;
    }, []);

    const go = (start: string, zoom = timeline.zoom) =>
        visitWithQuery(
            { start, zoom: zoom === 'weeks' ? null : zoom },
            { only: ONLY },
        );
    const step = (direction: number) =>
        go(
            timeline.zoom === 'weeks'
                ? addDays(timeline.from, 28 * direction)
                : addMonths(timeline.from, 3 * direction),
        );

    const span = (
        ticket: TicketListItem,
        current: Drag | null,
    ): [string, string] => {
        const [start, end] = spanOf(ticket);

        if (!current || current.id !== ticket.id || current.delta === 0) {
            return [start, end];
        }

        if (current.mode === 'move') {
            return [addDays(start, current.delta), addDays(end, current.delta)];
        }

        if (current.mode === 'start') {
            const next = addDays(start, current.delta);

            return [next > end ? end : next, end];
        }

        const next = addDays(end, current.delta);

        return [start, next < start ? start : next];
    };

    const preview = (ticket: TicketListItem) => span(ticket, drag);

    /**
     * The drag in progress is kept in a ref as well as state: pointer events
     * can arrive before React re-renders, and the release must see the
     * latest position.
     */
    const update = (next: Drag | null) => {
        dragRef.current = next;
        setDrag(next);
    };

    const deltaAt = (current: Drag, clientX: number) =>
        Math.round((clientX - current.originX) / dayWidth);

    const begin = (
        event: ReactPointerEvent,
        ticket: TicketListItem,
        mode: Drag['mode'],
    ) => {
        event.stopPropagation();
        event.currentTarget.setPointerCapture(event.pointerId);
        moved.current = false;
        update({ id: ticket.id, mode, originX: event.clientX, delta: 0 });
    };

    const track = (event: ReactPointerEvent) => {
        const current = dragRef.current;

        if (!current) {
            return;
        }

        const delta = deltaAt(current, event.clientX);

        if (delta !== current.delta) {
            moved.current = true;
            update({ ...current, delta });
        }
    };

    const finish = (event: ReactPointerEvent, ticket: TicketListItem) => {
        const current = dragRef.current;

        if (!current) {
            return;
        }

        const final = { ...current, delta: deltaAt(current, event.clientX) };
        update(null);

        if (final.delta === 0) {
            return;
        }

        moved.current = true;
        const [start, end] = span(ticket, final);
        const hadStart = ticket.starts_on !== null;
        // A ticket with no start date stays a one-day ticket when moved; once
        // a drag makes it span several days, its start date is saved too.
        const fields: Record<string, string> =
            hadStart || start !== end
                ? { starts_on: start, due_on: end }
                : { due_on: end };

        setItems(
            items.map((item) =>
                item.id === ticket.id ? { ...item, ...fields } : item,
            ),
        );
        updateTicket(ticket.id, fields);
    };

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                <h2 className="mr-2 text-lg font-semibold">
                    {dayFormatter.format(parseDay(timeline.from))} –{' '}
                    {dayFormatter.format(parseDay(timeline.to))}
                </h2>
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Earlier"
                        onClick={() => step(-1)}
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8"
                        onClick={() =>
                            go(
                                timeline.zoom === 'weeks'
                                    ? addDays(now, -14)
                                    : now,
                            )
                        }
                    >
                        Today
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Later"
                        onClick={() => step(1)}
                    >
                        <ChevronRight />
                    </Button>
                </div>
                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={timeline.zoom}
                    onValueChange={(zoom) =>
                        zoom &&
                        go(timeline.from, zoom as TimelineWindow['zoom'])
                    }
                    className="bg-card"
                    aria-label="Zoom"
                >
                    <ToggleGroupItem value="weeks" className="px-3">
                        Weeks
                    </ToggleGroupItem>
                    <ToggleGroupItem value="months" className="px-3">
                        Months
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>

            <div className="overflow-x-auto rounded-lg border bg-card shadow-xs">
                <div
                    style={{ width: LABEL_WIDTH + width }}
                    className="relative"
                >
                    <div className="sticky top-0 z-20 flex border-b bg-muted/80 backdrop-blur">
                        <div
                            style={{ width: LABEL_WIDTH }}
                            className="sticky left-0 z-10 shrink-0 border-r bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground"
                        >
                            Ticket
                        </div>
                        <div className="relative" style={{ width }}>
                            <div className="flex h-6">
                                {months.map((month) => (
                                    <div
                                        key={month.start}
                                        style={{
                                            width: month.count * dayWidth,
                                        }}
                                        className="truncate border-l px-1.5 text-xs leading-6 font-medium text-muted-foreground first:border-l-0"
                                    >
                                        {month.label}
                                    </div>
                                ))}
                            </div>
                            {timeline.zoom === 'weeks' && (
                                <div className="flex h-5">
                                    {days.map((day) => (
                                        <div
                                            key={day}
                                            style={{ width: dayWidth }}
                                            className={cn(
                                                'text-center text-[10px] leading-5 text-subtle-foreground',
                                                isWeekend(day) && 'bg-muted',
                                                day === now &&
                                                    'font-semibold text-primary',
                                            )}
                                        >
                                            {parseDay(day).getDate()}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {groups.length === 0 && (
                        <p className="px-4 py-10 text-center text-sm text-muted-foreground">
                            Nothing with dates in this window. Give tickets a
                            start or due date to plan them here.
                        </p>
                    )}

                    {groups.map((group) => (
                        <section key={group.project?.id ?? 'none'}>
                            <div
                                className={cn('flex border-b bg-muted/30', ROW)}
                            >
                                <div
                                    style={{ width: LABEL_WIDTH }}
                                    className="sticky left-0 z-10 flex shrink-0 items-center gap-2 border-r bg-muted px-3 text-sm font-medium"
                                >
                                    {group.project ? (
                                        <>
                                            <ProjectIcon
                                                icon={group.project.icon}
                                                color={group.project.color}
                                                size="sm"
                                            />
                                            <span className="truncate">
                                                {group.project.name}
                                            </span>
                                        </>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            No project
                                        </span>
                                    )}
                                </div>
                                <div className="relative" style={{ width }}>
                                    <Backdrop
                                        days={days}
                                        dayWidth={dayWidth}
                                        now={now}
                                    />
                                    {group.project?.starts_on &&
                                        group.project.due_on && (
                                            <span
                                                className={cn(
                                                    'absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full opacity-35',
                                                    colorDot[
                                                        group.project.color
                                                    ],
                                                )}
                                                style={{
                                                    left: Math.max(
                                                        0,
                                                        offset(
                                                            group.project
                                                                .starts_on,
                                                        ),
                                                    ),
                                                    width: Math.max(
                                                        dayWidth,
                                                        Math.min(
                                                            width,
                                                            offset(
                                                                group.project
                                                                    .due_on,
                                                            ) + dayWidth,
                                                        ) -
                                                            Math.max(
                                                                0,
                                                                offset(
                                                                    group
                                                                        .project
                                                                        .starts_on,
                                                                ),
                                                            ),
                                                    ),
                                                }}
                                            />
                                        )}
                                    {group.milestones.map((milestone) => (
                                        <Tooltip key={milestone.id}>
                                            <TooltipTrigger asChild>
                                                <span
                                                    aria-label={`Milestone: ${milestone.name}`}
                                                    className={cn(
                                                        'absolute top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rotate-45 rounded-[2px] border-2 border-card shadow-xs',
                                                        milestone.completed_at
                                                            ? 'bg-success'
                                                            : 'bg-foreground',
                                                    )}
                                                    style={{
                                                        left:
                                                            offset(
                                                                milestone.due_on,
                                                            ) +
                                                            dayWidth / 2,
                                                    }}
                                                />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {milestone.name} ·{' '}
                                                {dayFormatter.format(
                                                    parseDay(milestone.due_on),
                                                )}
                                            </TooltipContent>
                                        </Tooltip>
                                    ))}
                                </div>
                            </div>

                            {group.tickets.map((ticket) => {
                                const [start, end] = preview(ticket);
                                const left = Math.max(0, offset(start));
                                const right = Math.min(
                                    width,
                                    offset(end) + dayWidth,
                                );
                                const isClosed = ticket.status === 'closed';
                                const color = ticket.project_color
                                    ? colorDot[ticket.project_color]
                                    : 'bg-slate-400';

                                return (
                                    <div
                                        key={ticket.id}
                                        className={cn(
                                            'flex border-b last:border-b-0',
                                            ROW,
                                        )}
                                    >
                                        <button
                                            type="button"
                                            onClick={() =>
                                                openTicket(ticket.id)
                                            }
                                            style={{ width: LABEL_WIDTH }}
                                            className="sticky left-0 z-10 flex shrink-0 items-center gap-2 border-r bg-card px-3 pl-8 text-left text-sm hover:bg-accent"
                                        >
                                            <span className="font-mono text-[11px] text-subtle-foreground">
                                                #{ticket.number}
                                            </span>
                                            <span
                                                className={cn(
                                                    'truncate',
                                                    isClosed &&
                                                        'text-muted-foreground line-through',
                                                )}
                                            >
                                                {ticket.title}
                                            </span>
                                        </button>
                                        <div
                                            className="relative"
                                            style={{ width }}
                                        >
                                            <Backdrop
                                                days={days}
                                                dayWidth={dayWidth}
                                                now={now}
                                            />
                                            {right > left && (
                                                <div
                                                    role="button"
                                                    tabIndex={0}
                                                    aria-label={`${ticket.title}, ${dayFormatter.format(parseDay(start))} to ${dayFormatter.format(parseDay(end))}`}
                                                    onPointerDown={(event) =>
                                                        begin(
                                                            event,
                                                            ticket,
                                                            'move',
                                                        )
                                                    }
                                                    onPointerMove={track}
                                                    onPointerUp={(event) =>
                                                        finish(event, ticket)
                                                    }
                                                    onClick={() =>
                                                        !moved.current &&
                                                        openTicket(ticket.id)
                                                    }
                                                    onKeyDown={(event) =>
                                                        event.key === 'Enter' &&
                                                        openTicket(ticket.id)
                                                    }
                                                    className={cn(
                                                        'group absolute top-1.5 bottom-1.5 flex cursor-grab touch-none items-center rounded-md px-2 text-xs font-medium text-white shadow-xs select-none active:cursor-grabbing',
                                                        color,
                                                        isClosed &&
                                                            'opacity-45',
                                                        drag?.id ===
                                                            ticket.id &&
                                                            'ring-2 ring-ring/50',
                                                    )}
                                                    style={{
                                                        left,
                                                        width: right - left,
                                                    }}
                                                >
                                                    {dayWidth >= 20 && (
                                                        <span className="truncate">
                                                            {ticket.title}
                                                        </span>
                                                    )}
                                                    {(
                                                        [
                                                            'start',
                                                            'end',
                                                        ] as const
                                                    ).map((edge) => (
                                                        <span
                                                            key={edge}
                                                            aria-hidden
                                                            onPointerDown={(
                                                                event,
                                                            ) =>
                                                                begin(
                                                                    event,
                                                                    ticket,
                                                                    edge,
                                                                )
                                                            }
                                                            onPointerMove={
                                                                track
                                                            }
                                                            onPointerUp={(
                                                                event,
                                                            ) =>
                                                                finish(
                                                                    event,
                                                                    ticket,
                                                                )
                                                            }
                                                            className={cn(
                                                                'absolute inset-y-0 z-[2] w-2.5 cursor-ew-resize rounded-sm group-hover:bg-black/15',
                                                                edge === 'start'
                                                                    ? '-left-1'
                                                                    : '-right-1',
                                                            )}
                                                        />
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </section>
                    ))}
                </div>
            </div>
        </div>
    );
}

/**
 * Weekend shading and the line for today behind a row's bars.
 */
function Backdrop({
    days,
    dayWidth,
    now,
}: {
    days: string[];
    dayWidth: number;
    now: string;
}) {
    const todayIndex = days.indexOf(now);

    return (
        <>
            {dayWidth >= 20 &&
                days.map((day, index) =>
                    isWeekend(day) ? (
                        <span
                            key={day}
                            className="absolute inset-y-0 bg-muted/60"
                            style={{ left: index * dayWidth, width: dayWidth }}
                        />
                    ) : null,
                )}
            {todayIndex >= 0 && (
                <span
                    className="absolute inset-y-0 z-[1] w-px bg-primary"
                    style={{ left: todayIndex * dayWidth + dayWidth / 2 }}
                />
            )}
        </>
    );
}
