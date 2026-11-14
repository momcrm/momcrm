import { Head, setLayoutProps } from '@inertiajs/react';
import { Settings2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ConfirmDelete from '@/components/confirm-delete';
import GroupFormDialog from '@/components/groups/group-form-dialog';
import ProjectCard from '@/components/projects/project-card';
import SoftBadge from '@/components/soft-badge';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import TicketRow from '@/components/tickets/ticket-row';
import { Button } from '@/components/ui/button';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { colorDot } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { members as membersRoute } from '@/routes';
import { destroy, show } from '@/routes/groups';
import type {
    ColorName,
    CustomerOption,
    ProjectListItem,
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

type Group = {
    id: string;
    name: string;
    description: string | null;
    color: ColorName;
    icon: string | null;
};
type GroupMember = {
    id: string;
    name: string;
    email: string;
    role: 'lead' | 'member';
};

/**
 * A group's page: what it's working on (its projects and their open
 * tickets) and who is in it.
 */
export default function ShowGroup({
    group,
    members,
    projects,
    tickets,
    teamMembers,
    colors,
    favoriteTicketIds,
    options,
    drawer,
}: {
    group: Group;
    members: GroupMember[];
    projects: ProjectListItem[];
    tickets: TicketListItem[];
    teamMembers: CustomerOption[];
    colors: ColorName[];
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openTicket } = useTicketActions();
    const [editing, setEditing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Members',
                href: membersRoute({ query: { tab: 'groups' } }),
            },
            { title: group.name, href: show(group.id) },
        ],
    });

    return (
        <>
            <Head title={group.name} />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-wrap items-start gap-3">
                    <span
                        className={cn(
                            'mt-1 size-9 rounded-lg',
                            colorDot[group.color],
                        )}
                    />
                    <div className="min-w-0 flex-1">
                        <h1 className="text-xl font-semibold">{group.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {group.description ?? `${members.length} members`}
                        </p>
                    </div>
                    <Button variant="outline" onClick={() => setEditing(true)}>
                        <Settings2 />
                        Edit group
                    </Button>
                    <ConfirmDelete
                        action={destroy.form(group.id)}
                        title={`Delete ${group.name}?`}
                        description="Its projects stay, without a group. This can't be undone."
                    />
                </header>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        <Panel title={`Projects ${projects.length}`}>
                            {projects.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No projects in this group yet. Pick it in a
                                    project's settings.
                                </p>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {projects.map((project) => (
                                        <ProjectCard
                                            key={project.id}
                                            project={project}
                                        />
                                    ))}
                                </div>
                            )}
                        </Panel>
                        <Panel title="Open tickets" flush>
                            {tickets.length === 0 ? (
                                <p className="px-4 pb-4 text-sm text-muted-foreground">
                                    Nothing open in this group's projects.
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
                                                onOpen={() =>
                                                    openTicket(ticket.id)
                                                }
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>
                    </div>

                    <Panel title={`Members ${members.length}`}>
                        {members.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No members yet.
                            </p>
                        ) : (
                            <ul className="space-y-2.5">
                                {members.map((member) => (
                                    <li
                                        key={member.id}
                                        className="flex items-center gap-2.5 text-sm"
                                    >
                                        <AssigneeAvatar
                                            name={member.name}
                                            className="size-7 text-[10px]"
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate font-medium">
                                                {member.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {member.email}
                                            </span>
                                        </span>
                                        <SoftBadge
                                            tone={
                                                member.role === 'lead'
                                                    ? 'primary'
                                                    : 'neutral'
                                            }
                                        >
                                            {member.role === 'lead'
                                                ? 'Lead'
                                                : 'Member'}
                                        </SoftBadge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>
            </div>

            <TicketDrawer ticket={drawer} options={options} />
            <GroupFormDialog
                key={String(editing)}
                open={editing}
                onOpenChange={setEditing}
                members={teamMembers}
                colors={colors}
                group={{
                    id: group.id,
                    name: group.name,
                    description: group.description,
                    color: group.color,
                    lead_id:
                        members.find((member) => member.role === 'lead')?.id ??
                        null,
                    member_ids: members.map((member) => member.id),
                }}
            />
        </>
    );
}

function Panel({
    title,
    flush = false,
    children,
}: {
    title: string;
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
            <h2 className="flex h-11 items-center px-4 text-sm font-medium">
                {title}
            </h2>
            <div className={cn(!flush && 'px-4')}>{children}</div>
        </section>
    );
}
