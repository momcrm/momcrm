import {
    CalendarDays,
    ChevronDown,
    ChevronUp,
    ChevronsUp,
    Equal,
    Minus,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import StatusPill from '@/components/status-pill';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { TicketLabel, Choice } from '@/types';

/**
 * The live choices in a list, plus any archived ones still in use by the
 * records on screen, so those records keep showing (and can keep) their value.
 */
function liveOrInUse(
    choices: Choice[],
    inUseIds: (string | null | undefined)[],
): Choice[] {
    return choices.filter(
        (choice) => !choice.is_archived || inUseIds.includes(choice.id),
    );
}

/**
 * Every one of the team's ticket statuses, archived ones included, for
 * looking a ticket's status up.
 */
function useAllTicketStatuses(): Choice[] {
    return usePage().props.choices?.ticket_status ?? [];
}

/**
 * The team's live ticket statuses in board order, plus any archived ones that
 * the given statuses (such as the tickets on screen) are in.
 */
export function useTicketStatuses(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(useAllTicketStatuses(), inUseIds);
}

/**
 * One of the team's statuses by id, archived or not; undefined for none or
 * an unknown id.
 */
export function useTicketStatus(statusId: string | null): Choice | undefined {
    const statuses = useAllTicketStatuses();

    return statuses.find((status) => status.id === statusId);
}

/**
 * Whether a status means the ticket is finished, from its category.
 */
export function isClosedStatus(statuses: Choice[], statusId: string | null) {
    return (
        statuses.find((status) => status.id === statusId)?.category === 'closed'
    );
}

/**
 * A check for closed statuses, for components that look at many tickets.
 */
export function useIsClosedStatus(): (statusId: string | null) => boolean {
    const statuses = useAllTicketStatuses();

    return (statusId) => isClosedStatus(statuses, statusId);
}

/**
 * The status a ticket moves to when it is reopened (`open`) or closed (`closed`).
 */
export function defaultStatusId(
    statuses: Choice[],
    category: 'open' | 'closed',
): string | undefined {
    const live = statuses.filter((status) => !status.is_archived);

    return (
        live.find(
            (status) => status.category === category && status.is_default,
        ) ?? live.find((status) => status.category === category)
    )?.id;
}

export function TicketStatusPill({ statusId }: { statusId: string | null }) {
    const status = useTicketStatus(statusId);

    return (
        <StatusPill hue={status?.hue ?? null}>
            {status?.name ?? 'No status'}
        </StatusPill>
    );
}

/**
 * The icons a priority can show, by the name stored on the choice.
 */
export const priorityIcons: Record<string, LucideIcon> = {
    'chevrons-up': ChevronsUp,
    'chevron-up': ChevronUp,
    equal: Equal,
    'chevron-down': ChevronDown,
    minus: Minus,
};

function useAllTicketPriorities(): Choice[] {
    return usePage().props.choices?.ticket_priority ?? [];
}

/**
 * The team's live ticket priorities, most urgent first, plus any archived
 * ones among the given priorities (such as the ticket's own).
 */
export function useTicketPriorities(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(useAllTicketPriorities(), inUseIds);
}

/**
 * One of the team's priorities by id, archived or not; undefined for none or
 * an unknown id.
 */
export function usePriority(priorityId: string | null): Choice | undefined {
    const priorities = useAllTicketPriorities();

    return priorities.find((priority) => priority.id === priorityId);
}

/**
 * A priority's icon in its colour, for menus and pickers that label it.
 */
export function PriorityGlyph({
    priority,
    className,
}: {
    priority: Choice | undefined;
    className?: string;
}) {
    const Icon = (priority?.icon && priorityIcons[priority.icon]) || Minus;

    return (
        <Icon
            aria-hidden
            className={cn(
                'size-3.5 shrink-0',
                priority ? 'hue-text' : 'text-subtle-foreground',
                className,
            )}
            style={priority ? hueStyle(priority.hue) : undefined}
        />
    );
}

export function PriorityIcon({
    priorityId,
    className,
}: {
    priorityId: string | null;
    className?: string;
}) {
    const priority = usePriority(priorityId);
    const label = priority ? `${priority.name} priority` : 'No priority';

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span aria-label={label} className="inline-flex">
                    <PriorityGlyph priority={priority} className={className} />
                </span>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

/**
 * The team's live ticket labels in order, plus any archived ones among the
 * given labels (such as the ticket's own).
 */
export function useTicketLabels(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(usePage().props.choices?.ticket_label ?? [], inUseIds);
}

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});

/**
 * Days from today to a YYYY-MM-DD date: negative when it has passed.
 */
export function daysUntil(date: string): number {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Math.round(
        (new Date(`${date}T00:00:00`).getTime() - today.getTime()) / 86_400_000,
    );
}

export function formatDay(date: string): string {
    const days = daysUntil(date);

    if (days === 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'Tomorrow';
    }

    if (days === -1) {
        return 'Yesterday';
    }

    return dayFormatter.format(new Date(`${date}T00:00:00`));
}

/**
 * A ticket's due date, red once it has passed while the ticket is still open.
 */
export function DueChip({
    dueOn,
    isClosed,
}: {
    dueOn: string | null;
    isClosed: boolean;
}) {
    if (!dueOn) {
        return null;
    }

    const days = daysUntil(dueOn);

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs whitespace-nowrap',
                !isClosed && days < 0
                    ? 'font-medium text-destructive'
                    : !isClosed && days <= 1
                      ? 'text-warning'
                      : 'text-muted-foreground',
            )}
        >
            <CalendarDays className="size-3" />
            {formatDay(dueOn)}
        </span>
    );
}

export function LabelChips({
    labels,
    className,
}: {
    labels: TicketLabel[];
    className?: string;
}) {
    if (labels.length === 0) {
        return null;
    }

    return (
        <span className={cn('flex flex-wrap gap-1', className)}>
            {labels.map((label) => (
                <span
                    key={label.id}
                    className="inline-flex items-center gap-1 rounded-[5px] border bg-card px-1.5 py-px text-[11px] font-medium text-muted-foreground"
                >
                    <span
                        aria-hidden
                        className="size-1.5 rounded-full hue-dot"
                        style={hueStyle(label.hue)}
                    />
                    {label.name}
                </span>
            ))}
        </span>
    );
}

export function ProjectTag({
    name,
    hue,
}: {
    name: string | null;
    hue: number | null;
}) {
    if (!name) {
        return null;
    }

    return (
        <span className="inline-flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
            <span
                aria-hidden
                className="size-2 shrink-0 rounded-[3px] hue-dot"
                style={hueStyle(hue)}
            />
            <span className="truncate">{name}</span>
        </span>
    );
}

export function AssigneeAvatar({
    name,
    className,
}: {
    name: string | null;
    className?: string;
}) {
    const getInitials = useInitials();

    if (!name) {
        return (
            <span
                aria-label="Unassigned"
                className={cn(
                    'inline-flex size-5 shrink-0 rounded-full border border-dashed border-border-strong',
                    className,
                )}
            />
        );
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    aria-label={name}
                    className={cn(
                        'inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-accent text-[9px] font-semibold text-muted-foreground',
                        className,
                    )}
                >
                    {getInitials(name)}
                </span>
            </TooltipTrigger>
            <TooltipContent>{name}</TooltipContent>
        </Tooltip>
    );
}

export function SubtaskProgress({
    done,
    total,
}: {
    done: number;
    total: number;
}) {
    if (total === 0) {
        return null;
    }

    return (
        <span
            aria-label={`${done} of ${total} subtasks done`}
            className="inline-flex items-center gap-1.5 text-[11px] text-muted-foreground tabular-nums"
        >
            <span className="h-1 w-8 overflow-hidden rounded-full bg-muted">
                <span
                    className={cn(
                        'block h-full rounded-full',
                        done === total ? 'bg-success' : 'bg-primary',
                    )}
                    style={{ width: `${(done / total) * 100}%` }}
                />
            </span>
            {done}/{total}
        </span>
    );
}
