import { Head, Link, usePage } from '@inertiajs/react';
import { FolderKanban, Plus, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import GettingStartedChecklist from '@/components/getting-started-checklist';
import {
    MemberAvatars,
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
} from '@/components/projects/project-meta';
import SecurityNudge from '@/components/security-nudge';
import { describe } from '@/components/tickets/ticket-conversation';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import { daysUntil, formatDay } from '@/components/tickets/ticket-meta';
import TicketRow from '@/components/tickets/ticket-row';
import { Button } from '@/components/ui/button';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { timeAgo } from '@/lib/format';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { activity as activityRoute, dashboard, overview } from '@/routes';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as projectsIndex, show as showProject } from '@/routes/projects';
import { edit as teamEdit } from '@/routes/team';
import {
    create as createTicket,
    index as ticketsIndex,
    mine,
} from '@/routes/tickets';
import type {
    ActivityItem,
    ProjectListItem,
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

type Stats = {
    active_projects: number;
    open_tickets: number;
    closed_this_week: number;
    overdue_tickets: number;
    customers: number;
    unpaid_invoices: number;
    unpaid_total_in_cents: number;
    overdue_invoices: number;
};

type Segment = 'upcoming' | 'overdue' | 'completed';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

function greeting(): string {
    const hour = new Date().getHours();

    return hour < 12
        ? 'Good morning'
        : hour < 18
          ? 'Good afternoon'
          : 'Good evening';
}

/**
 * Home: a greeting, the team's numbers, your tickets, this week's deadlines,
 * active projects and the latest activity.
 */
export default function Dashboard({
    preferredName,
    stats,
    myTickets,
    deadlines,
    projects,
    activity,
    hasSecurity,
    options,
    drawer,
}: {
    preferredName: string;
    stats: Stats;
    myTickets: TicketListItem[];
    deadlines: TicketListItem[];
    projects: ProjectListItem[];
    activity: (ActivityItem & { url: string | null })[];
    hasSecurity: boolean;
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { gettingStarted } = usePage().props;
    const firstName = preferredName.split(' ')[0];

    return (
        <>
            <Head title="Home" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            {dateFormatter.format(new Date())}
                        </p>
                        <h1 className="text-2xl font-semibold">
                            {greeting()}, {firstName}
                        </h1>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={teamEdit()}>
                                <UserPlus />
                                Invite
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={projectsIndex({ query: { new: 1 } })}>
                                <FolderKanban />
                                New project
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={createTicket()}>
                                <Plus />
                                New ticket
                            </Link>
                        </Button>
                    </div>
                </header>

                {gettingStarted ? (
                    <GettingStartedChecklist
                        gettingStarted={gettingStarted}
                        preferredName={preferredName}
                    />
                ) : (
                    !hasSecurity && <SecurityNudge />
                )}

                <div className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-2 lg:grid-cols-4">
                    <Stat
                        label="Active projects"
                        value={stats.active_projects}
                        href={projectsIndex().url}
                    />
                    <Stat
                        label="Open tickets"
                        value={stats.open_tickets}
                        href={ticketsIndex().url}
                    />
                    <Stat
                        label="Closed this week"
                        value={stats.closed_this_week}
                        href={ticketsIndex({ query: { status: 'closed' } }).url}
                    />
                    <Stat
                        label="Overdue"
                        value={stats.overdue_tickets}
                        href={overview().url}
                        tone={
                            stats.overdue_tickets > 0
                                ? 'destructive'
                                : undefined
                        }
                    />
                </div>

                {stats.unpaid_invoices > 0 && (
                    <Link
                        href={invoicesIndex({ query: { status: 'sent' } })}
                        className="flex items-center justify-between rounded-lg border bg-card px-4 py-2.5 text-sm shadow-xs hover:border-border-strong"
                    >
                        <span>
                            <span className="font-medium">
                                {formatCents(stats.unpaid_total_in_cents)}
                            </span>{' '}
                            <span className="text-muted-foreground">
                                waiting on {stats.unpaid_invoices}{' '}
                                {stats.unpaid_invoices === 1
                                    ? 'invoice'
                                    : 'invoices'}
                            </span>
                        </span>
                        {stats.overdue_invoices > 0 && (
                            <span className="text-xs font-medium text-destructive">
                                {stats.overdue_invoices} overdue
                            </span>
                        )}
                    </Link>
                )}

                <div className="grid gap-4 lg:grid-cols-3">
                    <MyTickets tickets={myTickets} />
                    <Deadlines tickets={deadlines} />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel
                        title="Project progress"
                        href={projectsIndex().url}
                        className="lg:col-span-2"
                        flush
                    >
                        {projects.length === 0 ? (
                            <p className="px-4 pb-4 text-sm text-muted-foreground">
                                No active projects.
                            </p>
                        ) : (
                            <ul className="divide-y border-t">
                                {projects.map((project) => (
                                    <li key={project.id}>
                                        <Link
                                            href={showProject(project.id)}
                                            className="flex h-12 items-center gap-3 px-4 text-sm hover:bg-accent/50"
                                        >
                                            <ProjectIcon
                                                icon={project.icon}
                                                color={project.color}
                                                size="sm"
                                            />
                                            <span className="min-w-0 flex-1 truncate font-medium">
                                                {project.name}
                                            </span>
                                            <span className="hidden w-40 sm:block">
                                                <ProgressBar
                                                    closed={
                                                        project.closed_tickets_count
                                                    }
                                                    total={
                                                        project.tickets_count
                                                    }
                                                />
                                            </span>
                                            <ProjectStatusPill
                                                status={project.status}
                                            />
                                            <span className="hidden w-16 text-right text-xs text-muted-foreground md:block">
                                                {project.due_on
                                                    ? formatDay(project.due_on)
                                                    : '—'}
                                            </span>
                                            <span className="hidden md:block">
                                                <MemberAvatars
                                                    members={project.members}
                                                    max={3}
                                                />
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <Panel title="Recent activity" href={activityRoute().url}>
                        {activity.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nothing yet.
                            </p>
                        ) : (
                            <ol className="space-y-2.5">
                                {activity.map((item) => (
                                    <li key={item.id} className="text-sm">
                                        <p className="truncate">
                                            <span className="font-medium">
                                                {item.actor ?? 'Someone'}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                {describe(item)}
                                            </span>
                                            {typeof item.details?.number ===
                                                'number' &&
                                                (item.url ? (
                                                    <Link
                                                        href={item.url}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {' '}
                                                        #{item.details.number}
                                                    </Link>
                                                ) : (
                                                    ` #${item.details.number}`
                                                ))}
                                        </p>
                                        <p className="text-xs text-subtle-foreground">
                                            {timeAgo(item.created_at)}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </Panel>
                </div>
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

function Stat({
    label,
    value,
    href,
    tone,
}: {
    label: string;
    value: number;
    href: string;
    tone?: 'destructive';
}) {
    return (
        <Link
            href={href}
            className="flex flex-col gap-1 border-b p-4 last:border-b-0 hover:bg-accent/40 sm:border-r lg:border-b-0 lg:last:border-r-0"
        >
            <span className="text-xs text-muted-foreground">{label}</span>
            <span
                className={cn(
                    'text-2xl font-semibold tabular-nums',
                    tone === 'destructive' && 'text-destructive',
                )}
            >
                {value}
            </span>
        </Link>
    );
}

function Panel({
    title,
    href,
    className,
    flush = false,
    action,
    children,
}: {
    title: string;
    href?: string;
    className?: string;
    flush?: boolean;
    action?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'rounded-xl border bg-card shadow-xs',
                !flush && 'pb-4',
                className,
            )}
        >
            <header className="flex h-11 items-center gap-2 px-4">
                <h2 className="text-sm font-medium">{title}</h2>
                <span className="ml-auto flex items-center gap-2">
                    {action}
                    {href && (
                        <Link
                            href={href}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            View all
                        </Link>
                    )}
                </span>
            </header>
            <div className={cn(!flush && 'px-4')}>{children}</div>
        </section>
    );
}

function MyTickets({ tickets }: { tickets: TicketListItem[] }) {
    const { openTicket } = useTicketActions();
    const [segment, setSegment] = useState<Segment>('upcoming');
    const groups: Record<Segment, TicketListItem[]> = {
        upcoming: tickets.filter(
            (ticket) =>
                ticket.status !== 'closed' &&
                (!ticket.due_on || daysUntil(ticket.due_on) >= 0),
        ),
        overdue: tickets.filter(
            (ticket) =>
                ticket.status !== 'closed' &&
                ticket.due_on !== null &&
                daysUntil(ticket.due_on) < 0,
        ),
        completed: tickets.filter((ticket) => ticket.status === 'closed'),
    };
    const shown = groups[segment].slice(0, 6);

    return (
        <Panel
            title="My tickets"
            href={mine().url}
            className="lg:col-span-2"
            flush
            action={
                <nav
                    aria-label="My tickets"
                    className="flex gap-0.5 rounded-md bg-muted p-0.5"
                >
                    {(['upcoming', 'overdue', 'completed'] as const).map(
                        (item) => (
                            <button
                                key={item}
                                type="button"
                                onClick={() => setSegment(item)}
                                className={cn(
                                    'rounded-[5px] px-2 py-0.5 text-xs capitalize',
                                    segment === item
                                        ? 'bg-card shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {item}{' '}
                                <span className="text-subtle-foreground">
                                    {groups[item].length}
                                </span>
                            </button>
                        ),
                    )}
                </nav>
            }
        >
            {shown.length === 0 ? (
                <p className="px-4 pb-4 text-sm text-muted-foreground">
                    {segment === 'overdue'
                        ? 'Nothing overdue. Nice.'
                        : segment === 'completed'
                          ? 'Nothing finished in the last two weeks.'
                          : 'Nothing assigned to you right now.'}
                </p>
            ) : (
                <ul className="divide-y border-t">
                    {shown.map((ticket) => (
                        <li key={ticket.id}>
                            <TicketRow
                                ticket={ticket}
                                isFavorite={false}
                                onOpen={() => openTicket(ticket.id)}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </Panel>
    );
}

function Deadlines({ tickets }: { tickets: TicketListItem[] }) {
    const { openTicket } = useTicketActions();
    const buckets = [
        {
            label: 'Today',
            tickets: tickets.filter(
                (ticket) => ticket.due_on && daysUntil(ticket.due_on) === 0,
            ),
        },
        {
            label: 'Tomorrow',
            tickets: tickets.filter(
                (ticket) => ticket.due_on && daysUntil(ticket.due_on) === 1,
            ),
        },
        {
            label: 'This week',
            tickets: tickets.filter(
                (ticket) => ticket.due_on && daysUntil(ticket.due_on) > 1,
            ),
        },
    ];

    return (
        <Panel title="Upcoming deadlines">
            {tickets.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nothing due in the next week.
                </p>
            ) : (
                <div className="space-y-3">
                    {buckets
                        .filter((bucket) => bucket.tickets.length > 0)
                        .map((bucket) => (
                            <div key={bucket.label} className="space-y-1">
                                <h3 className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                    {bucket.label}
                                </h3>
                                <ul className="space-y-0.5">
                                    {bucket.tickets.map((ticket) => (
                                        <li key={ticket.id}>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openTicket(ticket.id)
                                                }
                                                className="flex w-full items-center gap-2 rounded-md px-1.5 py-1 text-left text-sm hover:bg-accent/60"
                                            >
                                                <span className="font-mono text-[11px] text-subtle-foreground">
                                                    #{ticket.number}
                                                </span>
                                                <span className="truncate">
                                                    {ticket.title}
                                                </span>
                                                {bucket.label === 'This week' &&
                                                    ticket.due_on && (
                                                        <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                                                            {formatDay(
                                                                ticket.due_on,
                                                            )}
                                                        </span>
                                                    )}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                </div>
            )}
        </Panel>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Home', href: dashboard() }],
};
