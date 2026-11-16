import { CalendarDays } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import type { ComponentType, SVGProps } from 'react';
import StatusPill from '@/components/status-pill';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { Choice, ChoiceList, GroupOption } from '@/types';

/**
 * The live choices in a list, plus any archived ones still in use by the
 * records on screen, so those records keep showing (and can keep) their value.
 */
export function liveOrInUse(
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
 * A status as a small circle in its colour, filling up as tickets move
 * through the open statuses and becoming a check once closed.
 */
export function StatusGlyph({
    status,
    className,
}: {
    status: Choice | undefined;
    className?: string;
}) {
    const statuses = useAllTicketStatuses().filter(
        (choice) => choice.category !== 'closed',
    );
    const classes = cn('size-3.5 shrink-0 hue-text', className);
    const style = hueStyle(status?.hue ?? null);

    if (status?.category === 'closed') {
        return (
            <svg
                viewBox="0 0 14 14"
                aria-hidden
                className={classes}
                style={style}
            >
                <circle cx="7" cy="7" r="6.2" fill="currentColor" />
                <path
                    d="M4.4 7.2 6.2 9 9.7 5.3"
                    fill="none"
                    stroke="var(--card)"
                    strokeWidth="1.6"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        );
    }

    const index = Math.max(
        0,
        statuses.findIndex((choice) => choice.id === status?.id),
    );
    const fraction = statuses.length > 1 ? index / statuses.length : 0;
    const angle = fraction * 2 * Math.PI;
    const end = {
        x: 7 + 3.6 * Math.sin(angle),
        y: 7 - 3.6 * Math.cos(angle),
    };

    return (
        <svg viewBox="0 0 14 14" aria-hidden className={classes} style={style}>
            <circle
                cx="7"
                cy="7"
                r="5.4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeDasharray={status ? undefined : '1.9 2.1'}
            />
            {fraction > 0 && (
                <path
                    d={`M7 3.4A3.6 3.6 0 ${fraction > 0.5 ? 1 : 0} 1 ${end.x.toFixed(2)} ${end.y.toFixed(2)}L7 7Z`}
                    fill="currentColor"
                />
            )}
        </svg>
    );
}

/**
 * A ticket's status as its glyph and name.
 */
export function TicketStatusLabel({
    statusId,
    className,
}: {
    statusId: string | null;
    className?: string;
}) {
    const status = useTicketStatus(statusId);

    return (
        <span
            className={cn(
                'inline-flex min-w-0 items-center gap-1.5',
                className,
            )}
        >
            <StatusGlyph status={status} />
            <span className="truncate">{status?.name ?? 'No status'}</span>
        </span>
    );
}

type GlyphProps = SVGProps<SVGSVGElement>;

/**
 * Signal bars for a priority: `filled` of the three bars in the current
 * colour, the rest in the border colour.
 */
function Bars({ filled, ...props }: GlyphProps & { filled: number }) {
    return (
        <svg viewBox="0 0 14 14" width="14" height="14" {...props}>
            {[
                { x: 1.8, y: 7.5, height: 4.5 },
                { x: 5.7, y: 4.5, height: 7.5 },
                { x: 9.6, y: 1.5, height: 10.5 },
            ].map((bar, index) => (
                <rect
                    key={bar.x}
                    x={bar.x}
                    y={bar.y}
                    width="2.6"
                    height={bar.height}
                    rx="1"
                    fill={
                        index < filled ? 'currentColor' : 'var(--border-strong)'
                    }
                />
            ))}
        </svg>
    );
}

function AlertSquare(props: GlyphProps) {
    return (
        <svg viewBox="0 0 14 14" width="14" height="14" {...props}>
            <rect
                x="1"
                y="1"
                width="12"
                height="12"
                rx="3"
                fill="currentColor"
            />
            <path
                d="M7 3.8v4"
                stroke="#fff"
                strokeWidth="1.7"
                strokeLinecap="round"
            />
            <circle cx="7" cy="10.1" r=".95" fill="#fff" />
        </svg>
    );
}

/**
 * The glyphs a priority can show, by the icon name stored on the choice:
 * an alert for the most urgent, then three, two, one or no signal bars.
 */
export const priorityIcons: Record<string, ComponentType<GlyphProps>> = {
    'chevrons-up': AlertSquare,
    'chevron-up': (props) => <Bars filled={3} {...props} />,
    equal: (props) => <Bars filled={2} {...props} />,
    'chevron-down': (props) => <Bars filled={1} {...props} />,
    minus: (props) => <Bars filled={0} {...props} />,
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
    const Icon =
        (priority?.icon && priorityIcons[priority.icon]) || priorityIcons.minus;
    const isAlert = priority?.icon === 'chevrons-up';

    return (
        <Icon
            aria-hidden
            className={cn(
                'size-3.5 shrink-0',
                isAlert ? 'hue-text' : 'text-soft-foreground',
                className,
            )}
            style={isAlert ? hueStyle(priority?.hue ?? 0) : undefined}
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
 * The team's live ticket types in order, plus any archived ones among the
 * given types (such as the ticket's own).
 */
export function useTicketTypes(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(usePage().props.choices?.ticket_type ?? [], inUseIds);
}

/**
 * One of the team's choices from a list by id, archived or not; undefined for
 * none or an unknown id.
 */
export function useChoice(
    list: ChoiceList,
    choiceId: string | null,
): Choice | undefined {
    return (usePage().props.choices?.[list] ?? []).find(
        (choice) => choice.id === choiceId,
    );
}

/**
 * A choice or group as a small dot in its colour and its name, such as a
 * ticket's type or group.
 */
export function ChoiceTag({
    choice,
    className,
}: {
    choice: Pick<Choice, 'name' | 'hue'>;
    className?: string;
}) {
    return (
        <span
            className={cn('inline-flex items-center gap-1.5', className)}
            style={hueStyle(choice.hue)}
        >
            <span className="size-2 shrink-0 rounded-full hue-dot" />
            {choice.name}
        </span>
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
    showIcon = true,
    className,
}: {
    dueOn: string | null;
    isClosed: boolean;
    showIcon?: boolean;
    className?: string;
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
                    ? 'text-destructive'
                    : !isClosed && days <= 1
                      ? 'text-warning'
                      : 'text-soft-foreground',
                className,
            )}
        >
            {showIcon && <CalendarDays className="size-3" />}
            {formatDay(dueOn)}
        </span>
    );
}

/**
 * An estimate in minutes, shortened: 30m, 4h, 1.5h, or 2d (of eight hours).
 */
export function formatEstimate(minutes: number): string {
    if (minutes < 60) {
        return `${minutes}m`;
    }

    if (minutes < 480) {
        return `${Math.round((minutes / 60) * 10) / 10}h`;
    }

    return `${Math.round((minutes / 480) * 10) / 10}d`;
}

export function ProjectTag({
    name,
    hue,
    className,
}: {
    name: string | null;
    hue: number | null;
    className?: string;
}) {
    if (!name) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground',
                className,
            )}
        >
            <span
                aria-hidden
                className="size-2 shrink-0 rounded-[3px] hue-dot"
                style={hueStyle(hue)}
            />
            <span className="truncate">{name}</span>
        </span>
    );
}

/**
 * A steady hue for a person's avatar, picked from their name.
 */
export function nameHue(name: string): number {
    let hash = 0;

    for (const character of name) {
        hash = (hash * 31 + character.charCodeAt(0)) % 360;
    }

    return (hash * 7) % 360;
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
                        'inline-flex size-5 shrink-0 items-center justify-center rounded-full hue-soft text-[8px] font-semibold tracking-[0.01em] shadow-[0_0_0_1.5px_var(--card)]',
                        className,
                    )}
                    style={hueStyle(nameHue(name))}
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

/**
 * The team's members as assignee choices: the ticket's group's members first,
 * under the group's name, then everyone else. Without a group (or when
 * everyone or no one is in it), just the members in their usual order.
 */
export function assigneeChoices(
    members: { id: string; name: string }[],
    group: GroupOption | undefined,
): { value: string; label: string; section?: string }[] {
    const inGroup = members.filter((member) =>
        group?.member_ids.includes(member.id),
    );

    if (!group || inGroup.length === 0 || inGroup.length === members.length) {
        return members.map((member) => ({
            value: member.id,
            label: member.name,
        }));
    }

    return [
        ...inGroup.map((member) => ({
            value: member.id,
            label: member.name,
            section: group.name,
        })),
        ...members
            .filter((member) => !group.member_ids.includes(member.id))
            .map((member) => ({
                value: member.id,
                label: member.name,
                section: 'Everyone else',
            })),
    ];
}
