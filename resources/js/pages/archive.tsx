import { Head, Link, router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import ConfirmDelete from '@/components/confirm-delete';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { ProjectIcon } from '@/components/projects/project-meta';
import { TicketStatusPill, formatDay } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { archive } from '@/routes';
import {
    destroy as destroyProject,
    restore as restoreProject,
} from '@/routes/projects';
import {
    destroy as destroyTicket,
    restore as restoreTicket,
} from '@/routes/tickets';
import type { Abilities, ProjectListItem, TicketListItem } from '@/types';

type Tab = 'tickets' | 'projects';

/**
 * Archived tickets and projects. Restoring puts them back where they were;
 * deleting is for good.
 */
export default function ArchivePage({
    tab,
    tickets,
    projects,
}: {
    tab: Tab;
    tickets: (TicketListItem & { can: Abilities })[];
    projects: (ProjectListItem & { can: Abilities })[];
}) {
    const restore = (url: string) =>
        router.post(url, {}, { preserveScroll: true });

    return (
        <>
            <Head title="Archive" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Archive"
                    description="Tickets and projects put away. Restore them, or delete them for good."
                />

                <nav
                    aria-label="Archive"
                    className="flex gap-0.5 self-start rounded-md bg-muted p-0.5"
                >
                    {(['tickets', 'projects'] as const).map((item) => (
                        <Link
                            key={item}
                            href={archive({
                                query: item === 'tickets' ? {} : { tab: item },
                            })}
                            preserveScroll
                            className={cn(
                                'rounded-[5px] px-3 py-0.5 text-sm capitalize',
                                tab === item
                                    ? 'bg-card shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item}{' '}
                            <span className="text-xs text-subtle-foreground">
                                {item === 'tickets'
                                    ? tickets.length
                                    : projects.length}
                            </span>
                        </Link>
                    ))}
                </nav>

                {tab === 'tickets' ? (
                    tickets.length === 0 ? (
                        <EmptyState
                            title="No archived tickets"
                            description="Tickets you archive show up here."
                        />
                    ) : (
                        <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                            {tickets.map((ticket) => (
                                <li
                                    key={ticket.id}
                                    className="flex items-center gap-3 px-3 py-2 text-sm"
                                >
                                    <span className="w-12 font-mono text-xs text-subtle-foreground">
                                        #{ticket.number}
                                    </span>
                                    <span className="min-w-0 flex-1 truncate">
                                        {ticket.title}
                                    </span>
                                    <span className="hidden text-xs text-muted-foreground md:block">
                                        {ticket.project_name ??
                                            ticket.customer_name}
                                    </span>
                                    <TicketStatusPill
                                        statusId={ticket.status_id}
                                    />
                                    <span className="hidden w-24 text-xs text-subtle-foreground lg:block">
                                        {ticket.archived_at &&
                                            `Archived ${formatDay(ticket.archived_at.slice(0, 10))}`}
                                    </span>
                                    {ticket.can.restore && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                restore(
                                                    restoreTicket.url(
                                                        ticket.id,
                                                    ),
                                                )
                                            }
                                        >
                                            <RotateCcw />
                                            Restore
                                        </Button>
                                    )}
                                    {ticket.can.delete && (
                                        <ConfirmDelete
                                            action={destroyTicket.form(
                                                ticket.id,
                                            )}
                                            title={`Delete ticket #${ticket.number}?`}
                                            description="It's gone for good, with its subtasks. This can't be undone."
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>
                    )
                ) : projects.length === 0 ? (
                    <EmptyState
                        title="No archived projects"
                        description="Projects you archive show up here."
                    />
                ) : (
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                        {projects.map((project) => (
                            <li
                                key={project.id}
                                className="flex items-center gap-3 px-3 py-2 text-sm"
                            >
                                <ProjectIcon
                                    icon={project.icon}
                                    hue={project.hue}
                                    size="sm"
                                />
                                <span className="min-w-0 flex-1 truncate font-medium">
                                    {project.name}
                                </span>
                                <span className="hidden text-xs text-muted-foreground md:block">
                                    {project.tickets_count} tickets
                                </span>
                                {project.can.restore && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            restore(
                                                restoreProject.url(project.id),
                                            )
                                        }
                                    >
                                        <RotateCcw />
                                        Restore
                                    </Button>
                                )}
                                {project.can.delete && (
                                    <ConfirmDelete
                                        action={destroyProject.form(project.id)}
                                        title={`Delete ${project.name}?`}
                                        description={`The project and its ${project.tickets_count} tickets are gone for good. This can't be undone.`}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

ArchivePage.layout = {
    breadcrumbs: [{ title: 'Archive', href: archive() }],
};
