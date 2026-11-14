import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Action = { action: string; method: 'post' };

/**
 * Invite people to the current team by email: one or more addresses and a role.
 */
export default function InviteForm({
    action,
    submitLabel = 'Send invites',
    secondaryAction,
}: {
    action: Action;
    submitLabel?: string;
    secondaryAction?: ReactNode;
}) {
    return (
        <Form {...action} resetOnSuccess={['emails']} className="space-y-6">
            {({ processing, errors }) => {
                const emailError =
                    errors.emails ??
                    errors.email_list ??
                    Object.entries(errors).find(([key]) =>
                        key.startsWith('email_list.'),
                    )?.[1];

                return (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="emails">Email addresses</Label>
                            <Textarea
                                id="emails"
                                name="emails"
                                rows={3}
                                placeholder="name@business.com, name@business.com"
                            />
                            <InputError message={emailError} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="role">Role</Label>
                            <NativeSelect
                                id="role"
                                name="role"
                                defaultValue="member"
                            >
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </NativeSelect>
                            <p className="text-sm text-muted-foreground">
                                Admins can invite people too.
                            </p>
                            <InputError message={errors.role} />
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            {secondaryAction}
                            <Button
                                disabled={processing}
                                data-test="send-invites-button"
                            >
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                        </div>
                    </>
                );
            }}
        </Form>
    );
}
