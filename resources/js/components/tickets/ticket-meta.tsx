import {
    CalendarDays,
    ChevronDown,
    ChevronUp,
    ChevronsUp,
    Equal,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { colorDot, colorSoft } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type {
    ColorName,
    TicketLabel,
    TicketPriority,
    TicketStatus,
    Tone,
} from '@/types';

export const ticketStatuses: {
    value: TicketStatus;
    label: string;
    tone: Tone;
}[] = [
    { value: 'new', label: 'New', tone: 'neutral' },
    { value: 'in_progress', label: 'In progress', tone: 'primary' },
    {
        value: 'waiting_on_customer',
        label: 'Waiting on customer',
        tone: 'warning',
    },
    {
        value: 'waiting_on_supplier',
        label: 'Waiting on supplier',
        tone: 'info',
    },
    { value: 'closed', label: 'Closed', tone: 'success' },
];

const statusByValue = Object.fromEntries(
    ticketStatuses.map((status) => [status.value, status]),
) as Record<TicketStatus, (typeof ticketStatuses)[number]>;

export function statusLabel(status: TicketStatus): string {
    return statusByValue[status].label;
}

export function statusTone(status: TicketStatus): Tone {
    return statusByValue[status].tone;
}

export function TicketStatusPill({ status }: { status: TicketStatus }) {
    return (
        <StatusPill tone={statusTone(status)}>{statusLabel(status)}</StatusPill>
    );
}

export const ticketPriorities: {
    value: TicketPriority;
    label: string;
    icon: LucideIcon;
    className: string;
}[] = [
    {
        value: 'urgent',
        label: 'Urgent',
        icon: ChevronsUp,
        className: 'text-destructive',
    },
    {
        value: 'high',
        label: 'High',
        icon: ChevronUp,
        className: 'text-warning',
    },
    {
        value: 'normal',
        label: 'Normal',
        icon: Equal,
        className: 'text-subtle-foreground',
    },
    {
        value: 'low',
        label: 'Low',
        icon: ChevronDown,
        className: 'text-subtle-foreground',
    },
];

export function priorityOf(priority: TicketPriority) {
    return (
        ticketPriorities.find((option) => option.value === priority) ??
        ticketPriorities[2]
    );
}

export function PriorityIcon({
    priority,
    className,
}: {
    priority: TicketPriority;
    className?: string;
}) {
    const { icon: Icon, label, className: color } = priorityOf(priority);

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Icon
                    aria-label={`${label} priority`}
                    className={cn('size-3.5 shrink-0', color, className)}
                />
            </TooltipTrigger>
            <TooltipContent>{label} priority</TooltipContent>
        </Tooltip>
    );
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
                    className={cn(
                        'rounded-sm px-1.5 py-px text-[11px] font-medium',
                        colorSoft[label.color],
                    )}
                >
                    {label.name}
                </span>
            ))}
        </span>
    );
}

export function ProjectTag({
    name,
    color,
}: {
    name: string | null;
    color: ColorName | null;
}) {
    if (!name || !color) {
        return null;
    }

    return (
        <span className="inline-flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
            <span
                aria-hidden
                className={cn('size-2 shrink-0 rounded-[3px]', colorDot[color])}
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
        <SoftBadge tone={done === total ? 'success' : 'neutral'}>
            {done}/{total}
        </SoftBadge>
    );
}
