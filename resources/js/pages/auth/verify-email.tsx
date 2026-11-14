// Components
import { Form, Head, usePage } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Check your inbox" />

            <p className="text-center text-sm text-muted-foreground">
                We sent a link to{' '}
                <span className="font-medium text-foreground">
                    {auth.user.email}
                </span>
                . Click it to confirm it's you and open your workspace.
            </p>

            {status === 'verification-link-sent' && (
                <div className="text-center text-sm font-medium text-green-600">
                    A fresh link is on its way. Check spam if it doesn't show up
                    in a minute.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Send it again
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Check your inbox',
    description: 'One click and you are in.',
};
