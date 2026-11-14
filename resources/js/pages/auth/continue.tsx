import { Form, Head } from '@inertiajs/react';
import SocialButtons from '@/components/auth/social-buttons';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SendEmailCodeController from '@/actions/App/Auth/Controllers/SendEmailCodeController';
import { login, register } from '@/routes';
import type { Option } from '@/types';

type Props = {
    mode: 'sign_up' | 'log_in';
    email: string | null;
    status?: string;
    socialProviders: Option[];
};

/**
 * One email field for both signing up and logging in, as in Notion. We email a
 * code either way, so the page never says whether an account exists.
 */
export default function Continue({
    mode,
    email,
    status,
    socialProviders,
}: Props) {
    const isSignUp = mode === 'sign_up';

    return (
        <>
            <Head title={isSignUp ? 'Sign up' : 'Log in'} />

            {/* The heading lives here, not in layout props, so it can't linger on the next page. */}
            <div className="-mt-3 text-center">
                <h1 className="text-lg leading-snug font-semibold">
                    {isSignUp
                        ? 'MomCRM: every client, every ticket.'
                        : 'Log in to MomCRM'}
                </h1>
                <p className="text-base text-muted-foreground">
                    {isSignUp ? 'Sign up with your work email' : 'Welcome back'}
                </p>
            </div>

            {status && (
                <p className="text-center text-sm font-medium text-green-600">
                    {status}
                </p>
            )}

            {!isSignUp && (
                <PasskeyVerify separator="Or continue with your email" />
            )}

            <Form
                {...SendEmailCodeController.form()}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-xs">
                                Work email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                defaultValue={email ?? ''}
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="name@company.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        {isSignUp && (
                            <p className="rounded-md bg-muted px-3 py-2 text-sm">
                                <strong>Tip:</strong> Use your work email so
                                it's easier for your team to join you on MomCRM.
                            </p>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            data-test="continue-with-email-button"
                        >
                            {processing && <Spinner />}
                            Continue
                        </Button>
                    </>
                )}
            </Form>

            <SocialButtons providers={socialProviders} />

            <p className="text-center text-sm text-muted-foreground">
                {isSignUp ? (
                    <>
                        Existing user?{' '}
                        <TextLink href={login()}>Log in</TextLink>
                    </>
                ) : (
                    <>
                        New to MomCRM?{' '}
                        <TextLink href={register()}>Sign up</TextLink>
                    </>
                )}
            </p>

            <p className="text-center text-xs text-muted-foreground">
                By continuing, you agree to MomCRM's terms and acknowledge its
                privacy policy.
            </p>
        </>
    );
}
