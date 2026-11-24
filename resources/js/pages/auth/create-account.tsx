import { Form, Head } from '@inertiajs/react';
import FormField from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import CreateAccountController from '@/actions/App/Auth/Controllers/CreateAccountController';

type Props = {
    email: string;
    suggestedName: { first_name: string; last_name: string } | null;
    passwordRules: string;
};

/**
 * Microsoft's "What's your name?": a first and last name, both required, and a
 * password only if you want one.
 */
export default function CreateAccount({
    email,
    suggestedName,
    passwordRules,
}: Props) {
    return (
        <>
            <Head title="Create your account" />

            <p className="-mt-2 text-center text-sm text-muted-foreground">
                You're signing up as{' '}
                <span className="font-medium text-foreground">{email}</span>.
            </p>

            <Form
                {...CreateAccountController.form()}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                id="first_name"
                                label="First name"
                                error={errors.first_name}
                            >
                                <Input
                                    id="first_name"
                                    name="first_name"
                                    defaultValue={
                                        suggestedName?.first_name ?? ''
                                    }
                                    required
                                    autoFocus
                                    maxLength={100}
                                    autoComplete="given-name"
                                    placeholder="Jane"
                                />
                            </FormField>

                            <FormField
                                id="last_name"
                                label="Last name"
                                error={errors.last_name}
                            >
                                <Input
                                    id="last_name"
                                    name="last_name"
                                    defaultValue={
                                        suggestedName?.last_name ?? ''
                                    }
                                    required
                                    maxLength={100}
                                    autoComplete="family-name"
                                    placeholder="Smith"
                                />
                            </FormField>
                        </div>

                        <FormField
                            id="password"
                            label="Set a password (optional)"
                            hint="Skip it and we'll email you a code each time you log in."
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                            />
                        </FormField>

                        <Button
                            type="submit"
                            className="w-full"
                            data-test="create-account-button"
                        >
                            {processing && <Spinner />}
                            Continue
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

CreateAccount.layout = {
    title: 'Welcome to MomCRM',
    description: 'First things first, tell us a bit about yourself.',
};
