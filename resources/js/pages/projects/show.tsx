import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    Archive,
    Check,
    MoreHorizontal,
    Plus,
    Settings2,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import EmptyState from '@/components/empty-state';
import Pager from '@/components/pager';
import { toggleProjectStar } from '@/components/projects/project-card';
import ProjectFormDialog from '@/components/projects/project-form-dialog';
import {
    MemberAvatars,
    ProjectIcon,
    ProjectStatusPill,
    RestrictedLock,
    StarMark,
    projectStatuses,
} from '@/components/projects/project-meta';
import TicketBoard from '@/components/tickets/ticket-board';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketGroups from '@/components/tickets/ticket-groups';
import {
    daysUntil,
    formatDay,
    ticketStatuses,
} from '@/components/tickets/ticket-meta';
import TicketRow from '@/components/tickets/ticket-row';
import TicketTable from '@/components/tickets/ticket-table';
import TicketToolbar from '@/components/tickets/ticket-toolbar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import {
    destroy as destroyMilestone,
    store as storeMilestone,
    toggle as toggleMilestone,
} from '@/routes/milestones';
import { archive, index, restore, show, update } from '@/routes/projects';
import type {
    ActivityItem,
    Milestone,
    Paginated,
    ProjectFormOptions,
    ProjectListItem,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
    TicketStatus,
} from '@/types';

type Tab = 'overview' | 'list' | 'board' | 'table';

type Props = {
    project: ProjectListItem & { group_name: string | null };
    tab: Tab;
    projectOptions: ProjectFormOptions;
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
    favoriteTicketIds: string[];
    statusCounts?: Partial<Record<TicketStatus, number>>;
    upcoming?: TicketListItem[];
    milestones?: Milestone[];
    activity?: ActivityItem[];
    tickets?: Paginated<TicketListItem>;
    filters?: TicketFilters;
};

const tabs: { value: Tab; label: string }[] = [
    { value: 'overview', label: 'Overview' },
    { value: 'list', label: 'List' },
    { value: 'board', label: 'Board' },
    { value: 'table', label: 'Table' },
];

export default function ShowProject(props: Props) {
    const { project, tab, projectOptions, options, drawer } = props;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Projects', href: index() },
            { title: project.name, href: show(project.id) },
        ],
    });
    const [editing, setEditing] = useState(false);
    const isArchived = project.archived_at !== null;

    return (
        <>
            <Head title={project.name} />

            {isArchived && (
                <div className="flex items-center gap-3 border-b bg-warning/10 px-4 py-2 text-sm md:px-6">
                    <Archive className="size-4 text-warning" />
                    <span className="flex-1">This project is archived.</span>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            router.post(
                                restore.url(project.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Restore
                    </Button>
                </div>
            )}

            <div className="border-b px-4 pt-5 md:px-6">
                <div className="flex flex-wrap items-center gap-3">
                    <ProjectIcon
                        icon={project.icon}
                        color={project.color}
                        size="lg"
                    />
                    <div className="min-w-0">
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            <span className="truncate">{project.name}</span>
                            {project.visibility === 'restricted' && (
                                <RestrictedLock />
                            )}
                            <button
                                type="button"
                                aria-label={
                                    project.is_favorite
                                        ? 'Unstar project'
                                        : 'Star project'
                                }
                                onClick={() => toggleProjectStar(project.id)}
                                className="rounded p-1 hover:bg-accent"
                            >
                                <StarMark isFavorite={project.is_favorite} />
                            </button>
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {project.customer_name ?? 'Internal project'}
                            {project.group_name && ` · ${project.group_name}`}
                        </p>
                    </div>
                    <div className="ml-auto flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                aria-label="Change status"
                                className="rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <ProjectStatusPill status={project.status} />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuRadioGroup
                                    value={project.status}
                                    onValueChange={(status) =>
                                        router.put(
                                            update.url(project.id),
                                            { status },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {projectStatuses.map((status) => (
                                        <DropdownMenuRadioItem
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </DropdownMenuRadioItem>
                                    ))}
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <MemberAvatars members={project.members} />
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditing(true)}
                        >
                            <Settings2 />
                            Settings
                        </Button>
                        {!isArchived && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        aria-label="More"
                                    >
                                        <MoreHorizontal />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        onSelect={() =>
                                            router.post(archive.url(project.id))
                                        }
                                    >
                                        <Archive />
                                        Move to archive
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                </div>

                <nav
                    aria-label="Project views"
                    className="mt-4 -mb-px flex gap-4"
                >
                    {tabs.map((item) => (
                        <Link
                            key={item.value}
                            href={show(project.id, {
                                query:
                                    item.value === 'overview'
                                        ? {}
                                        : { view: item.value },
                            })}
                            preserveScroll
                            className={cn(
                                'border-b-2 pb-2 text-sm',
                                tab === item.value
                                    ? 'border-foreground font-medium text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            </div>

            <div className="p-4 md:p-6">
                {tab === 'overview' ? (
                    <Overview {...props} />
                ) : (
                    <TicketTabs {...props} />
                )}
            </div>

            <TicketDrawer ticket={drawer} options={options} />
            <ProjectFormDialog
                key={`${project.id}-${editing}`}
                open={editing}
                onOpenChange={setEditing}
                options={projectOptions}
                project={project}
            />
        </>
    );
}

function TicketTabs({
    tab,
    tickets,
    filters,
    options,
    favoriteTicketIds,
}: Props) {
    if (!tickets || !filters) {
        return null;
    }

    return (
        <div className="flex flex-col gap-5">
            <TicketToolbar filters={filters} options={options} inProject />
            {tab === 'board' ? (
                <TicketBoard
                    tickets={tickets.data}
                    favoriteIds={favoriteTicketIds}
                />
            ) : tickets.data.length === 0 ? (
                <EmptyState
                    title="No tickets here"
                    description="Tickets put in this project show up here."
                />
            ) : tab === 'table' ? (
                <>
                    <TicketTable
                        tickets={tickets.data}
                        sort={{
                            column: filters.sort,
                            direction: filters.direction,
                        }}
                        onSortChange={({ column, direction }) =>
                            visitWithQuery(
                                { sort: column, direction },
                                { only: ['tickets', 'filters'] },
                            )
                        }
                    />
                    <Pager page={tickets} only="tickets" />
                </>
            ) : (
                <TicketGroups
                    key={filters.status}
                    favoriteIds={favoriteTicketIds}
                    groups={ticketStatuses.map((status) => ({
                        key: status.value,
                        label: status.label,
                        tone: status.tone,
                        tickets: tickets.data.filter(
                            (ticket) => ticket.status === status.value,
                        ),
                    }))}
                />
            )}
        </div>
    );
}

const statusBar: Record<TicketStatus, string> = {
    new: 'bg-muted-foreground/40',
    in_progress: 'bg-primary',
    waiting_on_customer: 'bg-warning',
    waiting_on_supplier: 'bg-info',
    closed: 'bg-success',
};

function Overview({
    project,
    statusCounts = {},
    upcoming = [],
    milestones = [],
    activity = [],
    favoriteTicketIds,
}: Props) {
    const { openTicket } = useTicketActions();
    const total = project.tickets_count;
    const percent =
        total === 0
            ? 0
            : Math.round((project.closed_tickets_count / total) * 100);

    return (
        <div className="grid gap-4 lg:grid-cols-3">
            <div className="flex flex-col gap-4 lg:col-span-2">
                <Panel title="About">
                    <p className="text-sm whitespace-pre-line text-muted-foreground">
                        {project.description ??
                            'No description yet. Add one in Settings.'}
                    </p>
                </Panel>

                <Panel title="Progress">
                    <div className="flex items-end gap-3">
                        <span className="text-3xl font-semibold tabular-nums">
                            {percent}%
                        </span>
                        <span className="pb-1 text-sm text-muted-foreground">
                            {project.closed_tickets_count} of {total} tickets
                            done
                            {project.overdue_tickets_count > 0 && (
                                <span className="text-destructive">
                                    {' '}
                                    · {project.overdue_tickets_count} overdue
                                </span>
                            )}
                        </span>
                    </div>
                    <div className="mt-3 flex h-2 overflow-hidden rounded-full bg-muted">
                        {ticketStatuses.map((status) => {
                            const count = statusCounts[status.value] ?? 0;

                            return count > 0 ? (
                                <span
                                    key={status.value}
                                    className={statusBar[status.value]}
                                    style={{
                                        width: `${(count / total) * 100}%`,
                                    }}
                                />
                            ) : null;
                        })}
                    </div>
                    <ul className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                        {ticketStatuses.map((status) => (
                            <li
                                key={status.value}
                                className="flex items-center gap-1.5"
                            >
                                <span
                                    className={cn(
                                        'size-2 rounded-full',
                                        statusBar[status.value],
                                    )}
                                />
                                {status.label}
                                <span className="tabular-nums">
                                    {statusCounts[status.value] ?? 0}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Panel>

                <Panel title="Coming up" flush>
                    {upcoming.length === 0 ? (
                        <p className="px-4 pb-4 text-sm text-muted-foreground">
                            Nothing with a due date yet.
                        </p>
                    ) : (
                        <ul className="divide-y border-t">
                            {upcoming.map((ticket) => (
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
                </Panel>

                <Panel title="Recent activity">
                    {activity.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No activity yet.
                        </p>
                    ) : (
                        <ol className="space-y-2.5">
                            {activity.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-baseline gap-2 text-sm"
                                >
                                    <span className="font-medium">
                                        {item.actor ?? 'Someone'}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {item.label.charAt(0).toLowerCase() +
                                            item.label.slice(1)}
                                        {typeof item.details?.number ===
                                            'number' &&
                                            ` #${item.details.number}`}
                                    </span>
                                    <span className="ml-auto shrink-0 text-xs text-subtle-foreground">
                                        {item.created_at &&
                                            formatDay(
                                                item.created_at.slice(0, 10),
                                            )}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    )}
                </Panel>
            </div>

            <div className="flex flex-col gap-4">
                <Panel title="Details">
                    <dl className="grid grid-cols-[6.5rem_1fr] gap-y-2 text-sm">
                        <Detail label="Customer">
                            {project.customer_name ?? 'Internal'}
                        </Detail>
                        <Detail label="Lead">{project.lead_name ?? '—'}</Detail>
                        {project.group_name && (
                            <Detail label="Group">{project.group_name}</Detail>
                        )}
                        <Detail label="Start">
                            {project.starts_on
                                ? formatDay(project.starts_on)
                                : '—'}
                        </Detail>
                        <Detail label="Due">
                            {project.due_on ? formatDay(project.due_on) : '—'}
                        </Detail>
                        <Detail label="Visibility">
                            {project.visibility === 'restricted'
                                ? 'Members only'
                                : 'Everyone in the team'}
                        </Detail>
                        <Detail label="Project">#{project.number}</Detail>
                    </dl>
                </Panel>
                <Milestones projectId={project.id} milestones={milestones} />
            </div>
        </div>
    );
}

function Milestones({
    projectId,
    milestones,
}: {
    projectId: string;
    milestones: Milestone[];
}) {
    const [adding, setAdding] = useState(false);

    return (
        <Panel
            title="Milestones"
            action={
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label="Add milestone"
                    onClick={() => setAdding(true)}
                >
                    <Plus />
                </Button>
            }
        >
            {milestones.length === 0 && !adding && (
                <p className="text-sm text-muted-foreground">
                    No milestones yet.
                </p>
            )}
            <ul className="space-y-1">
                {milestones.map((milestone) => {
                    const isDone = milestone.completed_at !== null;
                    const isLate = !isDone && daysUntil(milestone.due_on) < 0;

                    return (
                        <li
                            key={milestone.id}
                            className="group flex h-8 items-center gap-2 text-sm"
                        >
                            <button
                                type="button"
                                aria-label={
                                    isDone
                                        ? `Reopen ${milestone.name}`
                                        : `Complete ${milestone.name}`
                                }
                                onClick={() =>
                                    router.post(
                                        toggleMilestone.url(milestone.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className={cn(
                                    'flex size-4 shrink-0 rotate-45 items-center justify-center rounded-[3px] border',
                                    isDone
                                        ? 'border-success bg-success text-white'
                                        : 'border-border-strong',
                                )}
                            >
                                {isDone && (
                                    <Check className="size-3 -rotate-45" />
                                )}
                            </button>
                            <span
                                className={cn(
                                    'flex-1 truncate',
                                    isDone &&
                                        'text-muted-foreground line-through',
                                )}
                            >
                                {milestone.name}
                            </span>
                            <span
                                className={cn(
                                    'text-xs',
                                    isLate
                                        ? 'font-medium text-destructive'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {formatDay(milestone.due_on)}
                            </span>
                            <button
                                type="button"
                                aria-label={`Delete ${milestone.name}`}
                                onClick={() =>
                                    router.delete(
                                        destroyMilestone.url(milestone.id),
                                        { preserveScroll: true },
                                    )
                                }
                                className="text-subtle-foreground opacity-0 group-hover:opacity-100 hover:text-destructive focus-visible:opacity-100"
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </li>
                    );
                })}
            </ul>
            {adding && (
                <Form
                    {...storeMilestone.form(projectId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setAdding(false)}
                    className="mt-2 space-y-2"
                >
                    {({ errors, processing }) => (
                        <>
                            <Input
                                name="name"
                                placeholder="Milestone name"
                                aria-label="Milestone name"
                                autoFocus
                            />
                            <div className="flex gap-2">
                                <Input
                                    name="due_on"
                                    type="date"
                                    aria-label="Due date"
                                    className="flex-1"
                                />
                                <Button size="sm" disabled={processing}>
                                    Add
                                </Button>
                                <Button
                                    size="sm"
                                    type="button"
                                    variant="ghost"
                                    onClick={() => setAdding(false)}
                                >
                                    Cancel
                                </Button>
                            </div>
                            {(errors.name || errors.due_on) && (
                                <p className="text-xs text-destructive">
                                    {errors.name ?? errors.due_on}
                                </p>
                            )}
                        </>
                    )}
                </Form>
            )}
        </Panel>
    );
}

function Panel({
    title,
    action,
    flush = false,
    children,
}: {
    title: string;
    action?: ReactNode;
    flush?: boolean;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'rounded-xl border bg-card shadow-xs',
                !flush && 'pb-4',
            )}
        >
            <header className="flex h-11 items-center justify-between px-4">
                <h2 className="text-sm font-medium">{title}</h2>
                {action}
            </header>
            <div className={cn(!flush && 'px-4')}>{children}</div>
        </section>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="truncate">{children}</dd>
        </>
    );
}
