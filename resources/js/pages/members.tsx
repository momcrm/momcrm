import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search, UserPlus } from 'lucide-react';
import { useState } from 'react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import GroupFormDialog from '@/components/groups/group-form-dialog';
import InviteForm from '@/components/invite-form';
import PageHeader from '@/components/page-header';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import StoreTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/StoreTeamInvitationController';
import NativeSelect from '@/components/native-select';
import { colorDot, colorSoft } from '@/lib/colors';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { members as membersRoute } from '@/routes';
import { index as accessRulesIndex } from '@/routes/access-rules';
import { show as showGroup } from '@/routes/groups';
import { update as updateRole } from '@/routes/members';
import type { ColorName } from '@/types';

type Role = 'owner' | 'admin' | 'member';

type Member = {
    id: string;
    name: string;
    email: string;
    role: Role;
    groups: { id: string; name: string; color: ColorName }[];
    active_projects: number;
    open_tickets: number;
    last_active_at: string | null;
};

type Invitation = {
    id: string;
    email: string;
    role: Role;
    expires_at: string | null;
};

type Group = {
    id: string;
    name: string;
    description: string | null;
    color: ColorName;
    icon: string | null;
    members_count: number;
    projects_count: number;
};

type Tab = 'members' | 'groups' | 'roles';

const roleLabels: Record<Role, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

const column = dataTableColumns<Member>();

function memberColumns(canChangeRoles: boolean) {
    return column.columns([
        column.accessor('name', {
            header: 'Member',
            cell: (info) => (
                <span className="flex min-w-0 items-center gap-2.5">
                    <AssigneeAvatar
                        name={info.getValue()}
                        className="size-7 text-[10px]"
                    />
                    <span className="min-w-0">
                        <span className="block truncate font-medium">
                            {info.getValue()}
                        </span>
                        <span className="block truncate text-xs text-muted-foreground">
                            {info.row.original.email}
                        </span>
                    </span>
                </span>
            ),
        }),
        column.accessor('role', {
            header: 'Role',
            meta: { className: 'w-36' },
            cell: (info) =>
                canChangeRoles && info.getValue() !== 'owner' ? (
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
                        className="h-7 w-28 text-xs"
                    >
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                    </NativeSelect>
                ) : (
                    <SoftBadge
                        tone={
                            info.getValue() === 'owner' ? 'primary' : 'neutral'
                        }
                    >
                        {roleLabels[info.getValue()]}
                    </SoftBadge>
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
                                    'rounded-sm px-1.5 py-px text-[11px] font-medium',
                                    colorSoft[group.color],
                                )}
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
            cell: (info) => (
                <span className="text-muted-foreground tabular-nums">
                    {info.getValue()}
                </span>
            ),
        }),
        column.accessor('open_tickets', {
            header: 'Open tickets',
            meta: { className: 'hidden md:table-cell w-28 text-right' },
            cell: (info) => (
                <span className="text-muted-foreground tabular-nums">
                    {info.getValue()}
                </span>
            ),
        }),
        column.accessor('last_active_at', {
            header: 'Last active',
            meta: { className: 'hidden xl:table-cell w-32' },
            cell: (info) => (
                <span className="text-muted-foreground">
                    {info.getValue() ? timeAgo(info.getValue()) : '—'}
                </span>
            ),
        }),
        column.display({
            id: 'status',
            header: 'Status',
            meta: { className: 'w-24' },
            cell: () => <StatusPill tone="success">Active</StatusPill>,
        }),
    ]);
}

export default function Members({
    tab,
    members,
    invitations,
    groups,
    canInvite,
    canChangeRoles,
    colors,
}: {
    tab: Tab;
    members: Member[];
    invitations: Invitation[];
    groups: Group[];
    canInvite: boolean;
    canChangeRoles: boolean;
    roles: Role[];
    colors: ColorName[];
}) {
    const { canManageAccessRules } = usePage().props;
    const [search, setSearch] = useState('');
    const [role, setRole] = useState<Role | null>(null);
    const [inviting, setInviting] = useState(false);
    const [creatingGroup, setCreatingGroup] = useState(false);

    const shown = members.filter(
        (member) =>
            (!role || member.role === role) &&
            `${member.name} ${member.email}`
                .toLowerCase()
                .includes(search.toLowerCase()),
    );

    return (
        <>
            <Head title="Members" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Members"
                    description="The people in your team, the groups they're in and what they're working on."
                    actions={
                        tab === 'groups' ? (
                            <Button onClick={() => setCreatingGroup(true)}>
                                <Plus />
                                New group
                            </Button>
                        ) : canInvite ? (
                            <Button onClick={() => setInviting(true)}>
                                <UserPlus />
                                Invite
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
                            ['members', `Members ${members.length}`],
                            ['groups', `Groups ${groups.length}`],
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
                                    placeholder="Search members"
                                    aria-label="Search members"
                                    className="pl-9"
                                />
                            </div>
                            <div className="flex gap-1">
                                {(
                                    [null, 'owner', 'admin', 'member'] as const
                                ).map((value) => (
                                    <button
                                        key={value ?? 'all'}
                                        type="button"
                                        onClick={() => setRole(value)}
                                        className={cn(
                                            'rounded-full border px-2.5 py-0.5 text-xs',
                                            role === value
                                                ? 'border-primary/30 bg-primary/8 text-foreground'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {value ? roleLabels[value] : 'All'}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <DataTable
                            columns={memberColumns(canChangeRoles)}
                            data={shown}
                            getRowId={(member) => member.id}
                        />

                        {invitations.length > 0 && (
                            <section className="space-y-2">
                                <h2 className="text-sm font-medium">Invited</h2>
                                <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                                    {invitations.map((invitation) => (
                                        <li
                                            key={invitation.id}
                                            className="flex items-center gap-3 px-3 py-2.5 text-sm"
                                        >
                                            <span className="size-7 shrink-0 rounded-full border border-dashed border-border-strong" />
                                            <span className="min-w-0 flex-1 truncate">
                                                {invitation.email}
                                            </span>
                                            <SoftBadge tone="neutral">
                                                {roleLabels[invitation.role]}
                                            </SoftBadge>
                                            <StatusPill tone="warning">
                                                Invited
                                            </StatusPill>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
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
                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {groups.map((group) => (
                                <Link
                                    key={group.id}
                                    href={showGroup(group.id)}
                                    className="flex flex-col gap-2 rounded-xl border bg-card p-4 shadow-xs hover:border-border-strong"
                                >
                                    <span className="flex items-center gap-2 font-medium">
                                        <span
                                            className={cn(
                                                'size-3 rounded-[4px]',
                                                colorDot[group.color],
                                            )}
                                        />
                                        {group.name}
                                    </span>
                                    <span className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                                        {group.description ?? 'No description.'}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {group.members_count}{' '}
                                        {group.members_count === 1
                                            ? 'member'
                                            : 'members'}{' '}
                                        · {group.projects_count}{' '}
                                        {group.projects_count === 1
                                            ? 'project'
                                            : 'projects'}
                                    </span>
                                </Link>
                            ))}
                        </div>
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
                    <InviteForm action={StoreTeamInvitationController.form()} />
                </DialogContent>
            </Dialog>

            <GroupFormDialog
                open={creatingGroup}
                onOpenChange={setCreatingGroup}
                members={members.map((member) => ({
                    id: member.id,
                    name: member.name,
                }))}
                colors={colors}
            />
        </>
    );
}

Members.layout = {
    breadcrumbs: [{ title: 'Members', href: membersRoute() }],
};
