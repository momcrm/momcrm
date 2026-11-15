import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import PageHeader from '@/components/page-header';
import ProjectTable from '@/components/projects/project-table';
import {
    AssigneeAvatar,
    daysUntil,
    formatDay,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import { colorDot } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { overview } from '@/routes';
import { show as showProject } from '@/routes/projects';
import type { ColorName, ProjectListItem } from '@/types';

type Stats = {
    active_projects: number;
    open_tickets: number;
    closed_this_week: number;
    overdue_tickets: number;
    tickets: number;
    completion_rate: number;
    members: number;
};

type Workload = {
    id: string;
    name: string;
    total: number;
    /** Open tickets by status id. */
    statuses: Record<string, number>;
};

type UpcomingMilestone = {
    id: string;
    name: string;
    due_on: string;
    project_id: string;
    project_name: string | null;
    project_color: ColorName | null;
};

/**
 * The workspace at a glance: headline numbers, every project's progress,
 * tickets by status, each person's open work and the next milestones.
 */
export default function Overview({
    stats,
    portfolio,
    statusCounts,
    workload,
    milestones,
}: {
    stats: Stats;
    portfolio: ProjectListItem[];
    /** Tickets by status id. */
    statusCounts: Record<string, number>;
    workload: Workload[];
    milestones: UpcomingMilestone[];
}) {
    const [sort, setSort] = useState<{
        column: string;
        direction: 'asc' | 'desc';
    }>({ column: 'due_on', direction: 'asc' });
    const sorted = [...portfolio].sort((a, b) => {
        const value = (project: ProjectListItem): string =>
            sort.column === 'name'
                ? project.name
                : sort.column === 'status'
                  ? project.status
                  : (project.due_on ?? '\uffff');
        const order = value(a).localeCompare(value(b));

        return sort.direction === 'asc' ? order : -order;
    });
    const busiest = Math.max(1, ...workload.map((member) => member.total));
    const statuses = useTicketStatuses(...Object.keys(statusCounts));
    const openStatuses = statuses.filter(
        (status) => status.category !== 'closed',
    );

    return (
        <>
            <Head title="Overview" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Overview"
                    description="How the workspace is doing, across every project and person."
                />

                <div className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-2 lg:grid-cols-4">
                    <Figure
                        label="Active projects"
                        value={stats.active_projects}
                    />
                    <Figure
                        label="Tickets"
                        value={stats.tickets}
                        detail={`${stats.open_tickets} open · ${stats.overdue_tickets} overdue`}
                    />
                    <Figure
                        label="Completion rate"
                        value={`${stats.completion_rate}%`}
                        detail={`${stats.closed_this_week} closed this week`}
                    />
                    <Figure label="Members" value={stats.members} />
                </div>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium">Portfolio</h2>
                    <ProjectTable
                        projects={sorted}
                        sort={sort}
                        onSortChange={setSort}
                    />
                </section>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Panel title="Tickets by status">
                        <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                            {statuses.map((status) =>
                                (statusCounts[status.id] ?? 0) > 0 ? (
                                    <span
                                        key={status.id}
                                        className={colorDot[status.color]}
                                        style={{
                                            width: `${((statusCounts[status.id] ?? 0) / Math.max(1, stats.tickets)) * 100}%`,
                                        }}
                                    />
                                ) : null,
                            )}
                        </div>
                        <ul className="mt-4 space-y-2">
                            {statuses.map((status) => (
                                <li
                                    key={status.id}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <span
                                        className={cn(
                                            'size-2.5 rounded-full',
                                            colorDot[status.color],
                                        )}
                                    />
                                    {status.name}
                                    <span className="ml-auto text-muted-foreground tabular-nums">
                                        {statusCounts[status.id] ?? 0}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel title="Workload">
                        {workload.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No one in the team yet.
                            </p>
                        ) : (
                            <ul className="space-y-3">
                                {workload.map((member) => (
                                    <li
                                        key={member.id}
                                        className="flex items-center gap-3 text-sm"
                                    >
                                        <AssigneeAvatar
                                            name={member.name}
                                            className="size-6 text-[10px]"
                                        />
                                        <span className="w-28 truncate">
                                            {member.name}
                                        </span>
                                        <span className="flex h-2.5 flex-1 overflow-hidden rounded-full bg-muted">
                                            {openStatuses.map((status) =>
                                                (member.statuses[status.id] ??
                                                    0) > 0 ? (
                                                    <span
                                                        key={status.id}
                                                        title={`${status.name}: ${member.statuses[status.id]}`}
                                                        className={
                                                            colorDot[
                                                                status.color
                                                            ]
                                                        }
                                                        style={{
                                                            width: `${((member.statuses[status.id] ?? 0) / busiest) * 100}%`,
                                                        }}
                                                    />
                                                ) : null,
                                            )}
                                        </span>
                                        <span className="w-6 text-right text-muted-foreground tabular-nums">
                                            {member.total}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <Panel title="Upcoming milestones">
                    {milestones.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No milestones coming up.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {milestones.map((milestone) => {
                                const isLate = daysUntil(milestone.due_on) < 0;

                                return (
                                    <li
                                        key={milestone.id}
                                        className="flex h-10 items-center gap-3 text-sm"
                                    >
                                        <span className="size-2.5 rotate-45 rounded-[2px] bg-foreground" />
                                        <span className="font-medium">
                                            {milestone.name}
                                        </span>
                                        <Link
                                            href={showProject(
                                                milestone.project_id,
                                            )}
                                            className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            {milestone.project_color && (
                                                <span
                                                    className={cn(
                                                        'size-2 rounded-[3px]',
                                                        colorDot[
                                                            milestone
                                                                .project_color
                                                        ],
                                                    )}
                                                />
                                            )}
                                            <span className="truncate">
                                                {milestone.project_name}
                                            </span>
                                        </Link>
                                        <span
                                            className={cn(
                                                'ml-auto text-xs',
                                                isLate
                                                    ? 'font-medium text-destructive'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {isLate
                                                ? `Late · ${formatDay(milestone.due_on)}`
                                                : formatDay(milestone.due_on)}
                                        </span>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Panel>
            </div>
        </>
    );
}

function Figure({
    label,
    value,
    detail,
}: {
    label: string;
    value: number | string;
    detail?: string;
}) {
    return (
        <div className="flex flex-col gap-1 border-b p-4 last:border-b-0 sm:border-r lg:border-b-0 lg:last:border-r-0">
            <span className="text-xs text-muted-foreground">{label}</span>
            <span className="text-2xl font-semibold tabular-nums">{value}</span>
            {detail && (
                <span className="text-xs text-muted-foreground">{detail}</span>
            )}
        </div>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border bg-card p-4 shadow-xs">
            <h2 className="mb-3 text-sm font-medium">{title}</h2>
            {children}
        </section>
    );
}

Overview.layout = {
    breadcrumbs: [{ title: 'Overview', href: overview() }],
};
