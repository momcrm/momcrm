import { Form, Head } from '@inertiajs/react';
import SocialButtons from '@/components/auth/social-buttons';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SendPortalCodeController from '@/actions/App/Portal/Controllers/SendPortalCodeController';
import { redirect as socialRedirect } from '@/routes/portal/social';
import type { Option } from '@/types';

type Props = {
    email: string | null;
    socialProviders: Option[];
};

/**
 * Signing in to the customer portal: an email, which gets a code, or Google
 * or Microsoft. There's no signing up; the provider lets contacts in.
 */
export default function PortalLogin({ email, socialProviders }: Props) {
    return (
        <>
            <Head title="Customer portal" />

            <div className="-mt-3 text-center">
                <h1 className="text-lg leading-snug font-semibold">
                    Customer portal
                </h1>
                <p className="text-base text-muted-foreground">
                    Sign in to see your tickets, invoices and services
                </p>
            </div>

            <Form
                {...SendPortalCodeController.form()}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-xs">
                                Email
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

                        <Button type="submit" className="w-full">
                            {processing && <Spinner />}
                            Email me a code
                        </Button>
                    </>
                )}
            </Form>

            <SocialButtons
                providers={socialProviders}
                hrefFor={(provider) => socialRedirect.url(provider)}
            />

            <p className="text-center text-xs text-muted-foreground">
                No access yet? Ask your provider to let you into the portal.
            </p>
        </>
    );
}
