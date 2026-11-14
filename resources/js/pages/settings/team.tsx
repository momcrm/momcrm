import { Form, Head, Link } from '@inertiajs/react';
import UpdateTeamController from '@/actions/App/Team/Controllers/UpdateTeamController';
import DeleteTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/DeleteTeamInvitationController';
import StoreTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/StoreTeamInvitationController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import InviteForm from '@/components/invite-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/team';
import { index as teamActivityIndex } from '@/routes/team-activity';
import type { Team as TeamType } from '@/types';

type Member = {
    id: string;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
};

type Invitation = {
    id: string;
    email: string;
    role: 'admin' | 'member';
    expires_at: string;
};

const roleLabels: Record<Member['role'], string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

export default function Team({
    team,
    canUpdateTeam,
    canInvite,
    members,
    invitations,
}: {
    team: TeamType;
    canUpdateTeam: boolean;
    canInvite: boolean;
    members: Member[];
    invitations: Invitation[];
}) {
    return (
        <>
            <Head title="Team settings" />

            <h1 className="sr-only">Team settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Your team"
                    description={
                        canUpdateTeam
                            ? 'Everyone in a team shares the same customers, contacts and tickets.'
                            : 'Only the team owner can change these settings'
                    }
                />

                <Form
                    {...UpdateTeamController.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Team name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={team.name}
                                    name="name"
                                    required
                                    disabled={!canUpdateTeam}
                                    placeholder="Team name"
                                />

                                {canUpdateTeam && (
                                    <p className="text-sm text-muted-foreground">
                                        Most people use their company name. You
                                        can change it any time.
                                    </p>
                                )}

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            {canUpdateTeam && (
                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-team-button"
                                    >
                                        Save
                                    </Button>
                                </div>
                            )}
                        </>
                    )}
                </Form>
            </div>

            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Members"
                    description="People who can see this team's customers and tickets."
                />

                <ul className="divide-y rounded-lg border">
                    {members.map((member) => (
                        <li
                            key={member.id}
                            className="flex items-center justify-between gap-4 px-4 py-3"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">
                                    {member.name}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {member.email}
                                </p>
                            </div>
                            <Badge variant="secondary">
                                {roleLabels[member.role]}
                            </Badge>
                        </li>
                    ))}
                </ul>

                {canInvite && invitations.length > 0 && (
                    <ul className="divide-y rounded-lg border">
                        {invitations.map((invitation) => (
                            <li
                                key={invitation.id}
                                className="flex items-center justify-between gap-4 px-4 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {invitation.email}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Invited as {roleLabels[invitation.role]}{' '}
                                        · expires{' '}
                                        {new Intl.DateTimeFormat(undefined, {
                                            dateStyle: 'medium',
                                        }).format(
                                            new Date(invitation.expires_at),
                                        )}
                                    </p>
                                </div>
                                <Form
                                    {...DeleteTeamInvitationController.form(
                                        invitation.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            Revoke
                                        </Button>
                                    )}
                                </Form>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {canInvite && (
                <div className="space-y-4">
                    <Heading
                        variant="small"
                        title="Invite people"
                        description="They'll get an email with a link to join. Invitations expire after 7 days."
                    />
                    <InviteForm action={StoreTeamInvitationController.form()} />
                </div>
            )}

            {canUpdateTeam && (
                <div className="space-y-2">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="Who changed what in this team, and when."
                    />
                    <Button variant="outline" asChild>
                        <Link href={teamActivityIndex()} prefetch>
                            View team activity
                        </Link>
                    </Button>
                </div>
            )}
        </>
    );
}

Team.layout = {
    breadcrumbs: [
        {
            title: 'Team settings',
            href: edit(),
        },
    ],
};
