import { Form, Head, Link } from '@inertiajs/react';
import UpdateTeamController from '@/actions/App/Team/Controllers/UpdateTeamController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/team';
import { index as teamActivityIndex } from '@/routes/team-activity';
import type { Team as TeamType } from '@/types';

export default function Team({
    team,
    canUpdateTeam,
}: {
    team: TeamType;
    canUpdateTeam: boolean;
}) {
    return (
        <>
            <Head title="Team settings" />

            <h1 className="sr-only">Team settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Team"
                    description={
                        canUpdateTeam
                            ? "Update your current team's name"
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

            {canUpdateTeam && (
                <div className="space-y-2">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="See what happened in this team and every change to it"
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
