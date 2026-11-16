import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type Action = { action: string; method: 'post' };

/**
 * Invite people to the current team by email: one or more addresses, a role
 * and, when the team's groups are given, a group to join.
 */
export default function InviteForm({
    action,
    submitLabel = 'Send invites',
    secondaryAction,
    groups,
    onSuccess,
    inDialog = false,
}: {
    action: Action;
    submitLabel?: string;
    secondaryAction?: ReactNode;
    groups?: { id: string; name: string }[];
    onSuccess?: () => void;
    /** Put the buttons in a dialog footer, full width on a divider. */
    inDialog?: boolean;
}) {
    const Actions = inDialog ? DialogFooter : 'div';

    const hasGroups = groups !== undefined && groups.length > 0;

    return (
        <Form
            {...action}
            resetOnSuccess={['emails']}
            onSuccess={onSuccess}
            className={inDialog ? 'space-y-4' : 'space-y-6'}
        >
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

                        <div
                            className={cn(
                                'grid gap-4',
                                hasGroups && 'sm:grid-cols-2',
                            )}
                        >
                            <div className="grid content-start gap-2">
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

                            {hasGroups && (
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="group_id">Group</Label>
                                    <NativeSelect
                                        id="group_id"
                                        name="group_id"
                                        defaultValue=""
                                    >
                                        <option value="">No group</option>
                                        {groups.map((group) => (
                                            <option
                                                key={group.id}
                                                value={group.id}
                                            >
                                                {group.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <p className="text-sm text-muted-foreground">
                                        They join it when they accept.
                                    </p>
                                    <InputError message={errors.group_id} />
                                </div>
                            )}
                        </div>

                        <Actions className="flex items-center justify-end gap-2">
                            {secondaryAction}
                            <Button
                                disabled={processing}
                                data-test="send-invites-button"
                            >
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                        </Actions>
                    </>
                );
            }}
        </Form>
    );
}
