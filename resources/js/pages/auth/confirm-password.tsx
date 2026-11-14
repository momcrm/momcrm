import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import CodeInput, { CODE_LENGTH } from '@/components/auth/code-input';
import ConfirmWithCodeController from '@/actions/App/Auth/Controllers/ConfirmWithCodeController';
import SendConfirmationCodeController from '@/actions/App/Auth/Controllers/SendConfirmationCodeController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/passkey-verify';

function ConfirmWithCode({ email }: { email: string | null }) {
    const [code, setCode] = useState('');

    return (
        <div className="flex flex-col gap-4">
            <p className="text-center text-sm text-muted-foreground">
                We'll email a code to{' '}
                <span className="font-medium text-foreground">{email}</span>.
            </p>
            <Form {...SendConfirmationCodeController.form()}>
                {({ processing }) => (
                    <Button
                        type="submit"
                        variant="outline"
                        className="w-full"
                        disabled={processing}
                    >
                        Email me a code
                    </Button>
                )}
            </Form>
            <Form
                {...ConfirmWithCodeController.form()}
                className="flex flex-col gap-4"
                onError={() => setCode('')}
            >
                {({ processing, errors }) => (
                    <>
                        <CodeInput value={code} onChange={setCode} />
                        <InputError
                            className="text-center"
                            message={errors.code}
                        />
                        <Button
                            className="w-full"
                            disabled={processing || code.length < CODE_LENGTH}
                            data-test="confirm-code-button"
                        >
                            {processing && <Spinner />}
                            Confirm
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}

export default function ConfirmPassword({
    hasPassword,
    email,
}: {
    hasPassword: boolean;
    email: string | null;
}) {
    const [useCode, setUseCode] = useState(!hasPassword);

    return (
        <>
            <Head title="Confirm it's you" />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label="Confirm with passkey"
                loadingLabel="Confirming..."
                separator={
                    useCode
                        ? 'Or confirm with an emailed code'
                        : 'Or confirm with password'
                }
            />

            {useCode ? (
                <ConfirmWithCode email={email} />
            ) : (
                <Form {...store.form()} resetOnSuccess={['password']}>
                    {({ processing, errors }) => (
                        <div className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    placeholder="Password"
                                    autoComplete="current-password"
                                    autoFocus
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="confirm-password-button"
                                >
                                    {processing && <Spinner />}
                                    Confirm password
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            )}

            {hasPassword && (
                <button
                    type="button"
                    onClick={() => setUseCode(!useCode)}
                    className="text-center text-sm text-muted-foreground underline-offset-4 hover:underline"
                >
                    {useCode
                        ? 'Use my password instead'
                        : 'Email me a code instead'}
                </button>
            )}
        </>
    );
}

ConfirmPassword.layout = {
    title: "Confirm it's you",
    description: 'This part of MomCRM needs a quick check before you continue.',
};
