import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    CalendarDays,
    CircleAlert,
    CircleCheck,
    CircleDot,
    Clock,
    Copy,
    Crown,
    FolderKanban,
    Mail,
    Plus,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
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
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import TicketRow from '@/components/tickets/ticket-row';
import { Button } from '@/components/ui/button';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { hueStyle } from '@/lib/colors';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { members as membersRoute } from '@/routes';
import { show as showGroup } from '@/routes/groups';
import { show as showMember } from '@/routes/members';
import { show as showProject } from '@/routes/projects';
import { index as ticketsIndex } from '@/routes/tickets';
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

const joinedFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
});

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

    const copyEmail = () => {
        void navigator.clipboard
            .writeText(member.email)
            .then(() => toast.success('Email copied.'));
    };

    return (
        <>
            <Head title={member.name} />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-wrap items-start gap-4">
                    <span className="relative">
                        <AssigneeAvatar
                            name={member.name}
                            className="size-14 text-lg"
                        />
                        {online && (
                            <span
                                aria-label="Online"
                                className="absolute right-0 bottom-0 size-3.5 rounded-full border-2 border-background bg-success"
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
                        {member.groups.length > 0 && (
                            <div className="mt-1.5 flex flex-wrap gap-1">
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
                            <span className="flex items-center gap-1.5">
                                <Clock className="size-3.5" />
                                {online ? (
                                    <span className="text-success">
                                        Online now
                                    </span>
                                ) : member.last_active_at ? (
                                    `Active ${timeAgo(member.last_active_at)}`
                                ) : (
                                    'No activity yet'
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

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Stat
                        label="Active projects"
                        icon={FolderKanban}
                        value={stats.active_projects}
                    />
                    <Stat
                        label="Open tickets"
                        icon={CircleDot}
                        value={stats.open_tickets}
                        href={
                            ticketsIndex({ query: { assignee: member.id } }).url
                        }
                    />
                    <Stat
                        label="Closed tickets"
                        icon={CircleCheck}
                        value={stats.closed_tickets}
                    />
                    <Stat
                        label="Overdue"
                        icon={CircleAlert}
                        value={stats.overdue_tickets}
                        tone={
                            stats.overdue_tickets > 0
                                ? 'destructive'
                                : undefined
                        }
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <section className="rounded-xl border bg-card shadow-xs lg:col-span-2">
                        <nav
                            aria-label="Tickets"
                            className="flex gap-4 px-4 text-sm"
                        >
                            {(
                                [
                                    [
                                        'open',
                                        `Assigned · ${stats.open_tickets}`,
                                    ],
                                    [
                                        'closed',
                                        `Closed · ${stats.closed_tickets}`,
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
                            <p className="border-t px-4 py-4 text-sm text-muted-foreground">
                                {tab === 'open'
                                    ? 'Nothing assigned right now.'
                                    : 'No closed tickets yet.'}
                            </p>
                        ) : (
                            <ul className="divide-y border-t">
                                {tickets.map((ticket) => (
                                    <li key={ticket.id}>
                                        <TicketRow
                                            ticket={ticket}
                                            isFavorite={favoriteTicketIds.includes(
                                                ticket.id,
                                            )}
                                            onOpen={() => openTicket(ticket.id)}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <div className="flex flex-col gap-4">
                        <Panel title="Active projects">
                            {projects.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Not on any active projects.
                                </p>
                            ) : (
                                <ul className="space-y-1">
                                    {projects.map((project) => (
                                        <li key={project.id}>
                                            <Link
                                                href={showProject(project.id)}
                                                className="-mx-2 flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-accent/50"
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
                                <p className="text-sm text-muted-foreground">
                                    Nothing yet.
                                </p>
                            ) : (
                                <ol className="space-y-2.5">
                                    {activity.map((item) => (
                                        <li key={item.id} className="text-sm">
                                            <p className="truncate">
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
                                                            #
                                                            {
                                                                item.details
                                                                    .number
                                                            }
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

function Stat({
    label,
    icon: Icon,
    value,
    href,
    tone,
}: {
    label: string;
    icon: LucideIcon;
    value: number;
    href?: string;
    tone?: 'destructive';
}) {
    const body = (
        <>
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Icon className="size-3.5" />
                {label}
            </span>
            <span
                className={cn(
                    'text-2xl font-semibold tabular-nums',
                    tone === 'destructive' && 'text-destructive',
                )}
            >
                {value}
            </span>
        </>
    );
    const className =
        'flex flex-col gap-1 rounded-xl border bg-card p-4 shadow-xs';

    return href ? (
        <Link
            href={href}
            className={cn(className, 'transition hover:border-border-strong')}
        >
            {body}
        </Link>
    ) : (
        <div className={className}>{body}</div>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border bg-card pb-4 shadow-xs">
            <h2 className="flex h-11 items-center px-4 text-sm font-medium">
                {title}
            </h2>
            <div className="px-4">{children}</div>
        </section>
    );
}
