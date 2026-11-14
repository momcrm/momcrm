import { Form, Head, Link } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AcceptTeamInvitationController from '@/actions/App/TeamInvitation/Controllers/AcceptTeamInvitationController';
import { dashboard, home, login, logout, register } from '@/routes';

type Props =
    | { status: 'expired' }
    | {
          status: 'guest' | 'valid' | 'wrong-account';
          token: string;
          email: string;
          teamName: string;
          inviterName: string | null;
      };

export default function TeamInvitation(props: Props) {
    if (props.status === 'expired') {
        return (
            <>
                <Head title="Invitation expired" />
                <p className="text-center text-sm text-muted-foreground">
                    This invitation has expired or was already used. Ask whoever
                    invited you to send a new one.
                </p>
                <Button variant="outline" asChild>
                    <Link href={home()}>Go to MomCRM</Link>
                </Button>
            </>
        );
    }

    const from = props.inviterName
        ? `${props.inviterName} invited you`
        : 'You have been invited';

    return (
        <>
            <Head title={`Join ${props.teamName}`} />

            <p className="text-center text-sm text-muted-foreground">
                {from} to work on customers and tickets together in{' '}
                <span className="font-medium text-foreground">
                    {props.teamName}
                </span>
                . The invitation was sent to{' '}
                <span className="font-medium text-foreground">
                    {props.email}
                </span>
                .
            </p>

            {props.status === 'guest' && (
                <div className="flex flex-col gap-3">
                    <Button asChild>
                        <Link href={register()}>Create your account</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={login()}>I already have an account</Link>
                    </Button>
                    <p className="text-center text-xs text-muted-foreground">
                        Use {props.email} so we can match you to the invitation.
                    </p>
                </div>
            )}

            {props.status === 'valid' && (
                <Form
                    {...AcceptTeamInvitationController.form(props.token)}
                    className="flex flex-col gap-3"
                >
                    {({ processing }) => (
                        <Button
                            disabled={processing}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            Join {props.teamName}
                        </Button>
                    )}
                </Form>
            )}

            {props.status === 'wrong-account' && (
                <div className="flex flex-col gap-3 text-center text-sm">
                    <p className="text-muted-foreground">
                        You're signed in with a different email address. Log
                        out, then open the link again and sign in as{' '}
                        {props.email}.
                    </p>
                    <TextLink href={logout()}>Log out</TextLink>
                    <TextLink href={dashboard()}>Back to my dashboard</TextLink>
                </div>
            )}
        </>
    );
}

TeamInvitation.layout = {
    title: "You're invited",
    description: 'Join your team on MomCRM.',
};
