import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    CalendarDays,
    Clock,
    Copy,
    Crown,
    FolderKanban,
    History,
    ListChecks,
    Mail,
    MapPin,
    Plus,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import EmptyState from '@/components/empty-state';
import MemberActionsMenu from '@/components/members/member-actions-menu';
import { isOnline, roleLabels } from '@/components/members/member-meta';
import type { MemberTarget, Role } from '@/components/members/member-meta';
import RemoveMemberDialog from '@/components/members/remove-member-dialog';
import {
    ProjectIcon,
    ProjectStatusPill,
} from '@/components/projects/project-meta';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import { describe } from '@/components/tickets/ticket-conversation';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import {
    AssigneeAvatar,
    defaultStatusId,
    isClosedStatus,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import TicketRow from '@/components/tickets/ticket-row';
import { Button } from '@/components/ui/button';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { activitySubject } from '@/lib/activity';
import { hueStyle } from '@/lib/colors';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { members as membersRoute } from '@/routes';
import { show as showGroup } from '@/routes/groups';
import { show as showMember } from '@/routes/members';
import { show as showProject } from '@/routes/projects';
import { index as ticketsIndex, patch } from '@/routes/tickets';
import type {
    ActivityItem,
    ProjectStatus,
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

type Member = {
    id: string;
    name: string;
    email: string;
    job_title: string | null;
    timezone: string | null;
    role: Role;
    is_you: boolean;
    can_remove: boolean;
    joined_at: string | null;
    last_active_at: string | null;
    groups: { id: string; name: string; hue: number | null }[];
};

type Stats = {
    active_projects: number;
    open_tickets: number;
    closed_tickets: number;
    overdue_tickets: number;
};

type MemberProject = {
    id: string;
    name: string;
    icon: string | null;
    hue: number | null;
    status: ProjectStatus;
    is_lead: boolean;
};

type Tab = 'open' | 'closed';

/** The props that change when one of their tickets is closed or reopened. */
const RELOAD = [
    'openTickets',
    'closedTickets',
    'stats',
    'activity',
    'drawer',
    'sidebarCounts',
];

const joinedFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
});

/** The time it is now where someone is, e.g. "4:05 pm". */
function localTime(timezone: string): string | null {
    try {
        return new Intl.DateTimeFormat(undefined, {
            timeZone: timezone,
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date());
    } catch {
        return null;
    }
}

/**
 * Someone's page in the team: who they are, the tickets assigned to them,
 * the projects they work on and what they've been doing lately.
 */
export default function ShowMember({
    teamName,
    member,
    stats,
    openTickets,
    closedTickets,
    projects,
    activity,
    canChangeRoles,
    canInvite,
    favoriteTicketIds,
    options,
    drawer,
}: {
    teamName: string;
    member: Member;
    stats: Stats;
    openTickets: TicketListItem[];
    closedTickets: TicketListItem[];
    projects: MemberProject[];
    activity: (ActivityItem & { url: string | null })[];
    canChangeRoles: boolean;
    canInvite: boolean;
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openTicket } = useTicketActions();
    const { openCreateTicket } = useCreateTicket();
    const statuses = useTicketStatuses();
    const [tab, setTab] = useState<Tab>('open');
    const [removing, setRemoving] = useState<MemberTarget | null>(null);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Members', href: membersRoute() },
            { title: member.name, href: showMember(member.id) },
        ],
    });

    const target: MemberTarget = {
        ...member,
        pending: false,
        open_tickets: stats.open_tickets,
    };
    const tickets = tab === 'open' ? openTickets : closedTickets;
    const online = isOnline(member.last_active_at);
    const theirLocalTime = member.timezone ? localTime(member.timezone) : null;

    const copyEmail = () => {
        void navigator.clipboard
            .writeText(member.email)
            .then(() => toast.success('Email copied.'));
    };

    const toggleClosed = (ticket: TicketListItem) => {
        const statusId = defaultStatusId(
            statuses,
            isClosedStatus(statuses, ticket.status_id) ? 'open' : 'closed',
        );

        if (!statusId) {
            return;
        }

        router.patch(
            patch.url(ticket.id),
            { status_id: statusId },
            { only: RELOAD, preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            <Head title={member.name} />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-wrap items-start gap-4">
                    <span className="relative shrink-0">
                        <AssigneeAvatar
                            name={member.name}
                            className="size-16 text-xl"
                        />
                        {online && (
                            <span
                                aria-label="Online"
                                className="absolute right-0 bottom-0 size-4 rounded-full border-2 border-background bg-success"
                            />
                        )}
                    </span>
                    <div className="min-w-56 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold">
                                {member.name}
                                {member.is_you && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        (you)
                                    </span>
                                )}
                            </h1>
                            <span className="inline-flex items-center gap-1 rounded-sm bg-muted px-1.5 py-px text-xs font-medium text-muted-foreground">
                                {member.role === 'owner' && (
                                    <Crown className="size-3" />
                                )}
                                {roleLabels[member.role]}
                            </span>
                        </div>
                        {(member.job_title || member.groups.length > 0) && (
                            <div className="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
                                {member.job_title && (
                                    <span>{member.job_title}</span>
                                )}
                                {member.job_title &&
                                    member.groups.length > 0 && (
                                        <span aria-hidden>·</span>
                                    )}
                                {member.groups.map((group) => (
                                    <Link
                                        key={group.id}
                                        href={showGroup(group.id)}
                                        className="rounded-sm hue-soft px-1.5 py-px text-[11px] font-medium hover:underline"
                                        style={hueStyle(group.hue)}
                                    >
                                        {group.name}
                                    </Link>
                                ))}
                            </div>
                        )}
                        <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span className="flex items-center gap-1.5">
                                <Mail className="size-3.5" />
                                <span className="select-all">
                                    {member.email}
                                </span>
                            </span>
                            {member.timezone && (
                                <span className="flex items-center gap-1.5">
                                    <MapPin className="size-3.5" />
                                    {member.timezone.replaceAll('_', ' ')}
                                    {theirLocalTime && ` · ${theirLocalTime}`}
                                </span>
                            )}
                            <span className="flex items-center gap-1.5">
                                <Clock className="size-3.5" />
                                {online ? (
                                    <span className="text-success">
                                        Online now
                                    </span>
                                ) : member.last_active_at ? (
                                    `Active ${timeAgo(member.last_active_at)}`
                                ) : (
                                    'Never signed in'
                                )}
                            </span>
                            {member.joined_at && (
                                <span className="flex items-center gap-1.5">
                                    <CalendarDays className="size-3.5" />
                                    Joined{' '}
                                    {joinedFormatter.format(
                                        new Date(member.joined_at),
                                    )}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={copyEmail}>
                            <Copy />
                            Copy email
                        </Button>
                        <Button
                            onClick={() =>
                                openCreateTicket({ assigneeId: member.id })
                            }
                        >
                            <Plus />
                            Assign ticket
                        </Button>
                        <MemberActionsMenu
                            target={target}
                            canChangeRoles={canChangeRoles}
                            canInvite={canInvite}
                            showProfileLink={false}
                            onAssign={() =>
                                openCreateTicket({ assigneeId: member.id })
                            }
                            onRemove={setRemoving}
                            triggerClassName="size-control-md"
                        />
                    </div>
                </header>

                <div className="grid grid-cols-2 overflow-hidden rounded-xl border bg-card shadow-xs lg:grid-cols-4">
                    <Figure
                        label="Active projects"
                        value={stats.active_projects}
                    />
                    <Figure
                        label="Assigned tickets"
                        value={stats.open_tickets}
                        href={
                            ticketsIndex({ query: { assignee: member.id } }).url
                        }
                    />
                    <Figure
                        label="Completed tickets"
                        value={stats.closed_tickets}
                        onClick={() => setTab('closed')}
                    />
                    <Figure
                        label="Overdue"
                        value={stats.overdue_tickets}
                        tone={
                            stats.overdue_tickets > 0
                                ? 'destructive'
                                : undefined
                        }
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
                    <section className="self-start overflow-hidden rounded-xl border bg-card shadow-xs">
                        <nav
                            aria-label="Tickets"
                            className="flex gap-4 border-b px-4 text-sm"
                        >
                            {(
                                [
                                    [
                                        'open',
                                        `Assigned · ${stats.open_tickets}`,
                                    ],
                                    [
                                        'closed',
                                        `Completed · ${stats.closed_tickets}`,
                                    ],
                                ] as const
                            ).map(([key, label]) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => setTab(key)}
                                    aria-current={tab === key}
                                    className={cn(
                                        '-mb-px flex h-11 items-center border-b-2',
                                        tab === key
                                            ? 'border-foreground font-medium'
                                            : 'border-transparent text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {label}
                                </button>
                            ))}
                        </nav>
                        {tickets.length === 0 ? (
                            <EmptyState
                                compact
                                icon={ListChecks}
                                title="No tickets here"
                                description={
                                    tab === 'open'
                                        ? 'Nothing is assigned to them right now.'
                                        : 'They haven’t completed any tickets yet.'
                                }
                            />
                        ) : (
                            <ul className="divide-y">
                                {tickets.map((ticket) => (
                                    <li key={ticket.id}>
                                        <TicketRow
                                            ticket={ticket}
                                            isFavorite={favoriteTicketIds.includes(
                                                ticket.id,
                                            )}
                                            onOpen={() => openTicket(ticket.id)}
                                            onToggleClosed={() =>
                                                toggleClosed(ticket)
                                            }
                                            showAssignee={false}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <div className="flex flex-col gap-4">
                        <Panel title="Active projects">
                            {projects.length === 0 ? (
                                <EmptyState
                                    compact
                                    icon={FolderKanban}
                                    title="No active projects"
                                    description="They aren’t leading or working on any open projects."
                                />
                            ) : (
                                <ul className="px-2 pb-2">
                                    {projects.map((project) => (
                                        <li key={project.id}>
                                            <Link
                                                href={showProject(project.id)}
                                                className="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-accent/50"
                                            >
                                                <ProjectIcon
                                                    icon={project.icon}
                                                    hue={project.hue}
                                                    size="sm"
                                                />
                                                <span className="min-w-0 flex-1 truncate">
                                                    {project.name}
                                                </span>
                                                <span className="text-xs text-subtle-foreground">
                                                    {project.is_lead
                                                        ? 'Lead'
                                                        : 'Member'}
                                                </span>
                                                <ProjectStatusPill
                                                    status={project.status}
                                                />
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>

                        <Panel title="Recent activity">
                            {activity.length === 0 ? (
                                <EmptyState
                                    compact
                                    icon={History}
                                    title="No recent activity"
                                    description="What they do on tickets and projects shows up here."
                                />
                            ) : (
                                <ol className="space-y-3 px-4 pb-4">
                                    {activity.map((item) => (
                                        <ActivityRow
                                            key={item.id}
                                            item={item}
                                            actor={
                                                member.is_you
                                                    ? 'You'
                                                    : member.name
                                            }
                                        />
                                    ))}
                                </ol>
                            )}
                        </Panel>
                    </div>
                </div>
            </div>

            <TicketDrawer ticket={drawer} options={options} />
            <RemoveMemberDialog
                target={removing}
                teamName={teamName}
                onClose={() => setRemoving(null)}
            />
        </>
    );
}

function ActivityRow({
    item,
    actor,
}: {
    item: ActivityItem & { url: string | null };
    actor: string;
}) {
    const subject = activitySubject(item);

    return (
        <li className="flex items-start gap-2.5 text-sm">
            <AssigneeAvatar
                name={item.actor}
                className="mt-px size-5 text-[8px]"
            />
            <p className="min-w-0 flex-1">
                <span className="font-medium">{actor}</span>{' '}
                <span className="text-muted-foreground">{describe(item)}</span>
                {subject && (
                    <>
                        {' '}
                        {item.url ? (
                            <Link
                                href={item.url}
                                className="font-medium hover:underline"
                            >
                                {subject}
                            </Link>
                        ) : (
                            <span className="font-medium">{subject}</span>
                        )}
                    </>
                )}
            </p>
            <time
                dateTime={item.created_at ?? undefined}
                className="shrink-0 text-xs text-subtle-foreground"
            >
                {timeAgo(item.created_at)}
            </time>
        </li>
    );
}

/**
 * One number in the stats strip; a link or a button when it leads somewhere.
 */
function Figure({
    label,
    value,
    href,
    onClick,
    tone,
}: {
    label: string;
    value: number;
    href?: string;
    onClick?: () => void;
    tone?: 'destructive';
}) {
    const className = cn(
        'flex flex-col gap-1.5 border-b p-4 text-left last:border-b-0 odd:border-r lg:border-r lg:border-b-0 lg:last:border-r-0 [&:nth-last-child(2)]:border-b-0',
        (href || onClick) && 'transition-colors hover:bg-accent/50',
    );
    const body = (
        <>
            <span className="text-xs text-muted-foreground">{label}</span>
            <span
                className={cn(
                    'text-[22px] leading-tight font-semibold tracking-tight tabular-nums',
                    tone === 'destructive' && 'text-destructive',
                )}
            >
                {value}
            </span>
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {body}
            </Link>
        );
    }

    if (onClick) {
        return (
            <button type="button" onClick={onClick} className={className}>
                {body}
            </button>
        );
    }

    return <div className={className}>{body}</div>;
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border bg-card shadow-xs">
            <h2 className="flex h-11 items-center px-4 text-sm font-medium">
                {title}
            </h2>
            {children}
        </section>
    );
}
