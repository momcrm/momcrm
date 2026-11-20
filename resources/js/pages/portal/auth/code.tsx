import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import CodeInput, { CODE_LENGTH } from '@/components/auth/code-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import SendPortalCodeController from '@/actions/App/Portal/Controllers/SendPortalCodeController';
import VerifyPortalCodeController from '@/actions/App/Portal/Controllers/VerifyPortalCodeController';
import { login } from '@/routes/portal';

type Props = {
    email: string;
    minutesValid: number;
};

/**
 * "Check your email" for the customer portal: the 6-digit code.
 */
export default function PortalCode({ email, minutesValid }: Props) {
    const [code, setCode] = useState('');

    const verify = (value: string) =>
        router.post(
            VerifyPortalCodeController.url(),
            { code: value },
            { onError: () => setCode('') },
        );

    return (
        <>
            <Head title="Check your email" />

            <p className="-mt-2 text-center text-sm text-muted-foreground">
                If <span className="font-medium text-foreground">{email}</span>{' '}
                can use the portal, we've sent it a code. It works for{' '}
                {minutesValid} minutes.
            </p>

            <Form
                {...VerifyPortalCodeController.form()}
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
                            disabled={processing || code.length < CODE_LENGTH}
                        >
                            {processing && <Spinner />}
                            Continue
                        </Button>
                    </>
                )}
            </Form>

            <div className="flex flex-col items-center gap-2 text-sm">
                <Form {...SendPortalCodeController.form()}>
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="email" value={email} />
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
                <Link
                    href={login()}
                    className="text-muted-foreground underline-offset-4 hover:underline"
                >
                    Use a different email
                </Link>
            </div>
        </>
    );
}

PortalCode.layout = {
    title: 'Check your email',
};
