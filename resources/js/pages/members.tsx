import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Copy,
    Crown,
    FolderKanban,
    MoreHorizontal,
    Plus,
    Search,
    SquarePen,
    Ticket,
    UserMinus,
    UserPlus,
    Users,
} from 'lucide-react';
import { toast } from 'sonner';
import { useState } from 'react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import GroupFormDialog from '@/components/groups/group-form-dialog';
import InviteForm from '@/components/invite-form';
import PageHeader from '@/components/page-header';
import { MemberAvatars, ProjectIcon } from '@/components/projects/project-meta';
import StatusPill from '@/components/status-pill';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import DeleteTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/DeleteTeamInvitationController';
import StoreTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/StoreTeamInvitationController';
import RemoveMemberController from '@/actions/App/TeamUser/Controllers/RemoveMemberController';
import NativeSelect from '@/components/native-select';
import { hueStyle } from '@/lib/colors';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { members as membersRoute } from '@/routes';
import { index as accessRulesIndex } from '@/routes/access-rules';
import { show as showGroup } from '@/routes/groups';
import { update as updateRole } from '@/routes/members';
import { index as ticketsIndex } from '@/routes/tickets';

type Role = 'owner' | 'admin' | 'member';

type Member = {
    id: string;
    name: string;
    email: string;
    role: Role;
    groups: { id: string; name: string; hue: number | null }[];
    active_projects: number;
    open_tickets: number;
    last_active_at: string | null;
    is_you: boolean;
    can_remove: boolean;
};

/**
 * A row of the members table: someone in the team, or a pending invitation.
 */
type Row =
    | (Member & { pending: false })
    | (Omit<Member, 'active_projects' | 'open_tickets'> & {
          pending: true;
          active_projects: null;
          open_tickets: null;
      });

type Invitation = {
    id: string;
    email: string;
    role: Role;
    expires_at: string | null;
    group_id: string | null;
};

type Group = {
    id: string;
    name: string;
    description: string | null;
    hue: number | null;
    icon: string | null;
    members_count: number;
    projects_count: number;
    members: { id: string; name: string }[];
};

type Tab = 'members' | 'groups' | 'roles';

const roleLabels: Record<Role, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

const column = dataTableColumns<Row>();

/** Activity within this many minutes counts as being online. */
const onlineWithinMinutes = 5;

function isOnline(lastActiveAt: string | null): boolean {
    return (
        lastActiveAt !== null &&
        Date.now() - new Date(lastActiveAt).getTime() <
            onlineWithinMinutes * 60_000
    );
}

function Count({ value }: { value: number | null }) {
    return (
        <span className="text-muted-foreground tabular-nums">
            {value ?? '—'}
        </span>
    );
}

function memberColumns({
    canChangeRoles,
    onAssign,
    onRemove,
}: {
    canChangeRoles: boolean;
    onAssign: (row: Row) => void;
    onRemove: (row: Row) => void;
}) {
    return column.columns([
        column.accessor('name', {
            header: 'Member',
            cell: (info) => (
                <span className="flex min-w-0 items-center gap-2.5">
                    {info.row.original.pending ? (
                        <span className="size-7 shrink-0 rounded-full border border-dashed border-border-strong" />
                    ) : (
                        <AssigneeAvatar
                            name={info.getValue()}
                            className="size-7 text-[10px]"
                        />
                    )}
                    <span className="min-w-0">
                        <span className="block truncate font-medium">
                            {info.getValue()}
                            {info.row.original.is_you && (
                                <span className="font-normal text-muted-foreground">
                                    {' '}
                                    (you)
                                </span>
                            )}
                        </span>
                        {!info.row.original.pending && (
                            <span className="block truncate text-xs text-muted-foreground">
                                {info.row.original.email}
                            </span>
                        )}
                    </span>
                </span>
            ),
        }),
        column.accessor('role', {
            header: 'Role',
            meta: { className: 'w-36' },
            cell: (info) =>
                info.getValue() === 'owner' ? (
                    <span className="flex items-center gap-1.5">
                        <Crown className="size-3.5 text-muted-foreground" />
                        Owner
                    </span>
                ) : canChangeRoles && !info.row.original.pending ? (
                    <NativeSelect
                        aria-label={`Role for ${info.row.original.name}`}
                        value={info.getValue()}
                        onChange={(event) =>
                            router.patch(
                                updateRole.url(info.row.original.id),
                                { role: event.target.value },
                                { preserveScroll: true },
                            )
                        }
                        className="h-7 w-auto pr-7 pl-2 text-xs"
                    >
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                    </NativeSelect>
                ) : (
                    <span>{roleLabels[info.getValue()]}</span>
                ),
        }),
        column.accessor('groups', {
            header: 'Groups',
            meta: { className: 'hidden lg:table-cell' },
            cell: (info) =>
                info.getValue().length === 0 ? (
                    <span className="text-subtle-foreground">—</span>
                ) : (
                    <span className="flex flex-wrap gap-1">
                        {info.getValue().map((group) => (
                            <span
                                key={group.id}
                                className={cn(
                                    'hue-soft',
                                    'rounded-sm px-1.5 py-px text-[11px] font-medium',
                                )}
                                style={hueStyle(group.hue)}
                            >
                                {group.name}
                            </span>
                        ))}
                    </span>
                ),
        }),
        column.accessor('active_projects', {
            header: 'Projects',
            meta: { className: 'hidden md:table-cell w-24 text-right' },
            cell: (info) => <Count value={info.getValue()} />,
        }),
        column.accessor('open_tickets', {
            header: 'Open tickets',
            meta: { className: 'hidden md:table-cell w-28 text-right' },
            cell: (info) => <Count value={info.getValue()} />,
        }),
        column.accessor('last_active_at', {
            header: 'Last active',
            meta: { className: 'hidden xl:table-cell w-32' },
            cell: (info) =>
                isOnline(info.getValue()) ? (
                    <span className="text-success">Online</span>
                ) : (
                    <span className="text-muted-foreground">
                        {info.getValue() ? timeAgo(info.getValue()) : '—'}
                    </span>
                ),
        }),
        column.display({
            id: 'status',
            header: 'Status',
            meta: { className: 'w-24' },
            cell: (info) =>
                info.row.original.pending ? (
                    <StatusPill tone="warning">Invited</StatusPill>
                ) : (
                    <StatusPill tone="success">Active</StatusPill>
                ),
        }),
        column.display({
            id: 'actions',
            header: () => <span className="sr-only">Actions</span>,
            meta: { className: 'w-10' },
            cell: (info) => (
                <RowActions
                    row={info.row.original}
                    onAssign={onAssign}
                    onRemove={onRemove}
                />
            ),
        }),
    ]);
}

function RowActions({
    row,
    onAssign,
    onRemove,
}: {
    row: Row;
    onAssign: (row: Row) => void;
    onRemove: (row: Row) => void;
}) {
    const copyEmail = () => {
        void navigator.clipboard
            .writeText(row.email)
            .then(() => toast.success('Email copied.'));
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                aria-label={`More for ${row.name}`}
                className="flex size-7 items-center justify-center rounded-md text-soft-foreground outline-none hover:bg-accent hover:text-foreground data-[state=open]:bg-accent"
            >
                <MoreHorizontal className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {!row.pending && (
                    <>
                        <DropdownMenuItem asChild>
                            <Link
                                href={ticketsIndex({
                                    query: { assignee: row.id },
                                })}
                            >
                                <Ticket />
                                View their tickets
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => onAssign(row)}>
                            <SquarePen />
                            Assign a ticket
                        </DropdownMenuItem>
                    </>
                )}
                <DropdownMenuItem onSelect={copyEmail}>
                    <Copy />
                    Copy email
                </DropdownMenuItem>
                {(row.pending || row.can_remove) && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => onRemove(row)}
                        >
                            <UserMinus />
                            {row.pending
                                ? 'Revoke invitation'
                                : 'Remove from team'}
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function GroupCard({ group }: { group: Group }) {
    return (
        <Link
            href={showGroup(group.id)}
            className="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-xs hover:border-border-strong"
        >
            <span className="flex items-center gap-2.5 font-medium">
                <ProjectIcon icon={group.icon} hue={group.hue} />
                {group.name}
            </span>
            <span className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                {group.description ?? 'No description.'}
            </span>
            <span className="flex items-center gap-3 text-xs text-muted-foreground">
                <span className="flex items-center gap-1">
                    <Users className="size-3.5" />
                    {group.members_count}{' '}
                    {group.members_count === 1 ? 'member' : 'members'}
                </span>
                <span className="flex items-center gap-1">
                    <FolderKanban className="size-3.5" />
                    {group.projects_count}{' '}
                    {group.projects_count === 1 ? 'project' : 'projects'}
                </span>
                {group.members.length > 0 && (
                    <span className="ml-auto">
                        <MemberAvatars members={group.members} max={3} />
                    </span>
                )}
            </span>
        </Link>
    );
}

export default function Members({
    teamName,
    tab,
    members,
    invitations,
    groups,
    canInvite,
    canChangeRoles,
}: {
    teamName: string;
    tab: Tab;
    members: Member[];
    invitations: Invitation[];
    groups: Group[];
    canInvite: boolean;
    canChangeRoles: boolean;
    roles: Role[];
}) {
    const { canManageAccessRules } = usePage().props;
    const [search, setSearch] = useState('');
    const [role, setRole] = useState<Role | null>(null);
    const [inviting, setInviting] = useState(false);
    const [creatingGroup, setCreatingGroup] = useState(false);
    const [removing, setRemoving] = useState<Row | null>(null);
    const { openCreateTicket } = useCreateTicket();

    const rows: Row[] = [
        ...members.map((member) => ({ ...member, pending: false as const })),
        ...invitations.map((invitation) => ({
            id: invitation.id,
            name: invitation.email,
            email: invitation.email,
            role: invitation.role,
            groups: groups
                .filter((group) => group.id === invitation.group_id)
                .map(({ id, name, hue }) => ({ id, name, hue })),
            active_projects: null,
            open_tickets: null,
            last_active_at: null,
            is_you: false,
            can_remove: true,
            pending: true as const,
        })),
    ];

    const shown = rows.filter(
        (row) =>
            (!role || row.role === role) &&
            `${row.name} ${row.email}`
                .toLowerCase()
                .includes(search.toLowerCase()),
    );

    const columns = memberColumns({
        canChangeRoles,
        onAssign: (row) => openCreateTicket({ assigneeId: row.id }),
        onRemove: setRemoving,
    });

    return (
        <>
            <Head title="Members" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Members"
                    description={`${members.length} ${members.length === 1 ? 'person' : 'people'} in ${teamName}${invitations.length > 0 ? ` · ${invitations.length} pending` : ''}`}
                    actions={
                        canInvite ? (
                            <Button onClick={() => setInviting(true)}>
                                <UserPlus />
                                Invite member
                            </Button>
                        ) : undefined
                    }
                />

                <nav
                    aria-label="Members"
                    className="flex gap-4 border-b text-sm"
                >
                    {(
                        [
                            ['members', 'Members'],
                            ['groups', 'Groups'],
                            ['roles', 'Roles & permissions'],
                        ] as const
                    ).map(([value, label]) => (
                        <Link
                            key={value}
                            href={membersRoute({
                                query:
                                    value === 'members' ? {} : { tab: value },
                            })}
                            preserveScroll
                            className={cn(
                                '-mb-px border-b-2 pb-2',
                                tab === value
                                    ? 'border-foreground font-medium'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {label}
                        </Link>
                    ))}
                </nav>

                {tab === 'members' && (
                    <>
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="relative w-full sm:w-64">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search by name or email"
                                    aria-label="Search members"
                                    className="pl-9"
                                />
                            </div>
                            <div
                                role="group"
                                aria-label="Filter by role"
                                className="flex gap-0.5 rounded-md bg-muted p-0.5"
                            >
                                {(
                                    [null, 'owner', 'admin', 'member'] as const
                                ).map((value) => (
                                    <button
                                        key={value ?? 'all'}
                                        type="button"
                                        aria-pressed={role === value}
                                        onClick={() => setRole(value)}
                                        className={cn(
                                            'rounded-[5px] px-2.5 py-1 text-xs',
                                            role === value
                                                ? 'bg-card font-medium text-foreground shadow-xs'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {value ? roleLabels[value] : 'All'}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <DataTable
                            columns={columns}
                            data={shown}
                            getRowId={(row) => row.id}
                        />
                    </>
                )}

                {tab === 'groups' &&
                    (groups.length === 0 ? (
                        <EmptyState
                            title="No groups yet"
                            description="Group people who work together, such as Support or Field techs, and give projects to a group."
                            action={
                                <Button onClick={() => setCreatingGroup(true)}>
                                    New group
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">
                                    {groups.length}{' '}
                                    {groups.length === 1 ? 'group' : 'groups'}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setCreatingGroup(true)}
                                >
                                    <Plus />
                                    New group
                                </Button>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {groups.map((group) => (
                                    <GroupCard key={group.id} group={group} />
                                ))}
                            </div>
                        </>
                    ))}

                {tab === 'roles' && (
                    <div className="overflow-hidden rounded-lg border bg-card shadow-xs">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/60 text-left text-xs text-muted-foreground">
                                    <th className="px-3 py-2 font-medium">
                                        What they can do
                                    </th>
                                    <th className="w-24 px-3 py-2 text-center font-medium">
                                        Owner
                                    </th>
                                    <th className="w-24 px-3 py-2 text-center font-medium">
                                        Admin
                                    </th>
                                    <th className="w-24 px-3 py-2 text-center font-medium">
                                        Member
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(
                                    [
                                        [
                                            'Create and edit customers, tickets, projects and invoices',
                                            true,
                                            true,
                                            true,
                                        ],
                                        [
                                            'Delete, archive and restore them; manage project and group members',
                                            true,
                                            true,
                                            false,
                                        ],
                                        [
                                            'Change the access rules',
                                            true,
                                            true,
                                            false,
                                        ],
                                        [
                                            'Invite people and revoke invitations',
                                            true,
                                            true,
                                            false,
                                        ],
                                        [
                                            'Change members’ roles',
                                            true,
                                            false,
                                            false,
                                        ],
                                        [
                                            'Rename the team and see its audit log',
                                            true,
                                            false,
                                            false,
                                        ],
                                    ] as const
                                ).map(([label, ...allowed]) => (
                                    <tr
                                        key={label}
                                        className="border-b last:border-b-0"
                                    >
                                        <td className="px-3 py-2.5">{label}</td>
                                        {allowed.map((isAllowed, index) => (
                                            <td
                                                key={index}
                                                className="px-3 py-2.5 text-center"
                                            >
                                                {isAllowed ? (
                                                    <span className="text-success">
                                                        ✓
                                                    </span>
                                                ) : (
                                                    <span className="text-subtle-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <p className="border-t px-3 py-2.5 text-xs text-muted-foreground">
                            The first three rows follow the team's access rules,
                            shown here as they start out.
                            {canManageAccessRules && (
                                <>
                                    {' '}
                                    <Link
                                        href={accessRulesIndex()}
                                        className="font-medium text-foreground underline underline-offset-4"
                                    >
                                        Change the access rules
                                    </Link>
                                </>
                            )}
                        </p>
                    </div>
                )}
            </div>

            <Dialog open={inviting} onOpenChange={setInviting}>
                <DialogContent className="sm:max-w-lg">
                    <DialogTitle>Invite people</DialogTitle>
                    <DialogDescription>
                        They'll get an email with a link to join the team.
                    </DialogDescription>
                    <InviteForm
                        action={StoreTeamInvitationController.form()}
                        groups={groups}
                        onSuccess={() => setInviting(false)}
                        secondaryAction={
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                        }
                    />
                </DialogContent>
            </Dialog>

            <Dialog
                open={removing !== null}
                onOpenChange={(open) => !open && setRemoving(null)}
            >
                <DialogContent>
                    <DialogTitle>
                        {removing?.pending
                            ? 'Revoke invitation?'
                            : `Remove ${removing?.name ?? ''} from ${teamName}?`}
                    </DialogTitle>
                    <DialogDescription>
                        {removing?.pending
                            ? `The invitation link sent to ${removing.email} will stop working.`
                            : 'They’ll lose access to the team straight away and come off its groups, projects and open tickets. Their closed tickets and comments stay.'}
                    </DialogDescription>
                    {removing && (
                        <Form
                            {...(removing.pending
                                ? DeleteTeamInvitationController.form(
                                      removing.id,
                                  )
                                : RemoveMemberController.form(removing.id))}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setRemoving(null)}
                        >
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            variant="secondary"
                                            type="button"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        {removing.pending ? 'Revoke' : 'Remove'}
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            <GroupFormDialog
                open={creatingGroup}
                onOpenChange={setCreatingGroup}
                members={members.map((member) => ({
                    id: member.id,
                    name: member.name,
                }))}
            />
        </>
    );
}

Members.layout = {
    breadcrumbs: [{ title: 'Members', href: membersRoute() }],
};
