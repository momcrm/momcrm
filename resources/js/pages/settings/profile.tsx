import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import UpdateProfileController from '@/actions/App/User/Controllers/UpdateProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import NativeSelect from '@/components/native-select';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
    auth: Auth;
};

/** Every timezone the browser knows, e.g. "Australia/Perth". */
const timezones: string[] = Intl.supportedValuesOf('timeZone');
const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

export default function Profile({
    mustVerifyEmail,
    hasPassword,
    status,
}: {
    mustVerifyEmail: boolean;
    hasPassword: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name, email address and what teammates see on your member page"
                />

                <Form
                    {...UpdateProfileController.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Preferred name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="What should we call you?"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="grid gap-2">
                                <Label htmlFor="job_title">Job title</Label>

                                <Input
                                    id="job_title"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.job_title ?? ''}
                                    name="job_title"
                                    maxLength={100}
                                    autoComplete="organization-title"
                                    placeholder="e.g. Head of Support"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.job_title}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timezone">Timezone</Label>

                                <NativeSelect
                                    id="timezone"
                                    name="timezone"
                                    className="mt-1 w-full"
                                    defaultValue={auth.user.timezone ?? ''}
                                >
                                    <option value="">Not set</option>
                                    {timezones.map((timezone) => (
                                        <option key={timezone} value={timezone}>
                                            {timezone.replaceAll('_', ' ')}
                                            {timezone === browserTimezone
                                                ? ' (this device)'
                                                : ''}
                                        </option>
                                    ))}
                                </NativeSelect>

                                <InputError
                                    className="mt-2"
                                    message={errors.timezone}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser hasPassword={hasPassword} />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
