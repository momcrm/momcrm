import { Form, Head } from '@inertiajs/react';
import FormField from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import CreateAccountController from '@/actions/App/Auth/Controllers/CreateAccountController';

type Props = {
    email: string;
    suggestedName: string | null;
    passwordRules: string;
};

/**
 * Notion's "What should we call you?": a name, and a password only if you want one.
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
                        <FormField
                            id="name"
                            label="What should we call you?"
                            hint="A first name or nickname works. It's how MomCRM and your team will see you."
                            error={errors.name}
                        >
                            <Input
                                id="name"
                                name="name"
                                defaultValue={suggestedName ?? ''}
                                required
                                autoFocus
                                autoComplete="name"
                                placeholder="Jane"
                            />
                        </FormField>

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
