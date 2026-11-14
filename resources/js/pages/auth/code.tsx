import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import CodeInput, { CODE_LENGTH } from '@/components/auth/code-input';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SendEmailCodeController from '@/actions/App/Auth/Controllers/SendEmailCodeController';
import VerifyEmailCodeController from '@/actions/App/Auth/Controllers/VerifyEmailCodeController';
import { register } from '@/routes';
import { store as logInWithPassword } from '@/routes/login';
import { request as forgotPassword } from '@/routes/password';

type Props = {
    email: string;
    minutesValid: number;
    canResetPassword: boolean;
};

/**
 * "Check your email": the 6-digit code, or a password for people who set one.
 */
export default function Code({ email, minutesValid, canResetPassword }: Props) {
    const [code, setCode] = useState('');
    const [usePassword, setUsePassword] = useState(false);

    const verify = (value: string) =>
        router.post(
            VerifyEmailCodeController.url(),
            { code: value },
            { onError: () => setCode('') },
        );

    return (
        <>
            <Head title="Check your email" />

            <p className="-mt-2 text-center text-sm text-muted-foreground">
                We sent a code to{' '}
                <span className="font-medium text-foreground">{email}</span>. It
                works for {minutesValid} minutes.
            </p>

            {usePassword ? (
                <Form
                    {...logInWithPassword.form()}
                    resetOnSuccess={['password']}
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="email" value={email} />
                            <input type="hidden" name="remember" value="1" />
                            <div className="grid gap-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={forgotPassword()}
                                            className="text-sm"
                                        >
                                            Forgot it?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="current-password"
                                    autoFocus
                                    required
                                />
                                <InputError
                                    message={errors.email ?? errors.password}
                                />
                            </div>
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </>
                    )}
                </Form>
            ) : (
                <Form
                    {...VerifyEmailCodeController.form()}
                    className="flex flex-col gap-4"
                    onError={() => setCode('')}
                >
                    {({ processing, errors }) => (
                        <>
                            <CodeInput
                                value={code}
                                onChange={setCode}
                                onComplete={verify}
                                disabled={processing}
                            />
                            <InputError
                                className="text-center"
                                message={errors.code}
                            />
                            <Button
                                className="w-full"
                                disabled={
                                    processing || code.length < CODE_LENGTH
                                }
                                data-test="verify-code-button"
                            >
                                {processing && <Spinner />}
                                Continue
                            </Button>
                        </>
                    )}
                </Form>
            )}

            <div className="flex flex-col items-center gap-2 text-sm">
                {!usePassword && (
                    <Form {...SendEmailCodeController.form()}>
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="email"
                                    value={email}
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="text-muted-foreground underline-offset-4 hover:underline disabled:opacity-50"
                                >
                                    Send a new code
                                </button>
                            </>
                        )}
                    </Form>
                )}
                <button
                    type="button"
                    onClick={() => setUsePassword(!usePassword)}
                    className="text-muted-foreground underline-offset-4 hover:underline"
                >
                    {usePassword
                        ? 'Use a code instead'
                        : 'Use a password instead'}
                </button>
                <Link
                    href={register()}
                    className="text-muted-foreground underline-offset-4 hover:underline"
                >
                    Use a different email
                </Link>
            </div>
        </>
    );
}

Code.layout = {
    title: 'Check your email',
};
