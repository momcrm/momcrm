import { Head, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import ConfirmDelete from '@/components/confirm-delete';
import FormField from '@/components/form-field';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { timeAgo } from '@/lib/format';
import { connect, destroy, sync, update } from '@/routes/mailboxes';
import type { Mailbox } from '@/types';

type GroupOption = { id: string; name: string };

/**
 * Where "Reconnect" sends someone: back to Microsoft for the same mailbox.
 * These are full page loads, because Microsoft's page is not an Inertia page.
 */
function reconnectUrl(mailbox: Mailbox): string {
    return connect.url(
        mailbox.is_shared ? { query: { address: mailbox.address } } : undefined,
    );
}

export default function Email({
    mailboxes,
    groups,
    canConnect,
}: {
    mailboxes: Mailbox[];
    groups: GroupOption[];
    canConnect: boolean;
}) {
    const { errors } = usePage().props;
    const [connecting, setConnecting] = useState(false);

    const save = (mailbox: Mailbox, changes: Partial<Mailbox>) =>
        router.put(
            update.url(mailbox.id),
            {
                group_id: mailbox.group_id,
                auto_create_tickets: mailbox.auto_create_tickets,
                ...changes,
            },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="Email" />

            <h1 className="sr-only">Email</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Email"
                        description="Connect a Microsoft 365 mailbox, and mail sent to it opens tickets. Only mail that arrives after you connect it is read."
                    />
                    {canConnect && (
                        <Button
                            size="sm"
                            className="shrink-0"
                            onClick={() => setConnecting(true)}
                        >
                            <Plus />
                            Connect mailbox
                        </Button>
                    )}
                </div>

                <InputError message={errors?.address} />

                {!canConnect && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Microsoft sign-in isn’t set up for this installation, so
                        mailboxes can’t be connected yet.
                    </p>
                )}

                {canConnect && mailboxes.length === 0 && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No mailboxes connected yet.
                    </p>
                )}

                {mailboxes.length > 0 && (
                    <ul className="space-y-2">
                        {mailboxes.map((mailbox) => (
                            <li
                                key={mailbox.id}
                                className="space-y-3 rounded-lg border bg-card p-3 shadow-xs"
                            >
                                <div className="flex items-start gap-2">
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium break-all">
                                                {mailbox.address}
                                            </span>
                                            {mailbox.status === 'connected' ? (
                                                <SoftBadge tone="success">
                                                    Connected
                                                </SoftBadge>
                                            ) : (
                                                <SoftBadge tone="warning">
                                                    Needs reconnecting
                                                </SoftBadge>
                                            )}
                                            {mailbox.is_shared && (
                                                <SoftBadge tone="neutral">
                                                    Shared mailbox
                                                </SoftBadge>
                                            )}
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {[
                                                mailbox.display_name,
                                                mailbox.last_synced_at
                                                    ? `Checked ${timeAgo(mailbox.last_synced_at)}`
                                                    : 'Not checked yet',
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                        {mailbox.last_error && (
                                            <p className="text-sm text-destructive">
                                                {mailbox.last_error}
                                            </p>
                                        )}
                                    </div>
                                    {canConnect && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant={
                                                mailbox.status === 'connected'
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                        >
                                            <a href={reconnectUrl(mailbox)}>
                                                Reconnect
                                            </a>
                                        </Button>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-end justify-between gap-3 border-t pt-3">
                                    <div className="flex flex-wrap items-end gap-4">
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor={`mailbox-group-${mailbox.id}`}
                                            >
                                                New tickets go to
                                            </Label>
                                            <NativeSelect
                                                id={`mailbox-group-${mailbox.id}`}
                                                className="w-48"
                                                value={mailbox.group_id ?? ''}
                                                onChange={(event) =>
                                                    save(mailbox, {
                                                        group_id:
                                                            event.target
                                                                .value || null,
                                                    })
                                                }
                                            >
                                                <option value="">
                                                    No group
                                                </option>
                                                {groups.map((group) => (
                                                    <option
                                                        key={group.id}
                                                        value={group.id}
                                                    >
                                                        {group.name}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </div>
                                        <div className="flex h-control-md items-center gap-2">
                                            <Checkbox
                                                id={`mailbox-auto-create-${mailbox.id}`}
                                                checked={
                                                    mailbox.auto_create_tickets
                                                }
                                                onCheckedChange={(checked) =>
                                                    save(mailbox, {
                                                        auto_create_tickets:
                                                            checked === true,
                                                    })
                                                }
                                            />
                                            <Label
                                                htmlFor={`mailbox-auto-create-${mailbox.id}`}
                                                className="text-sm font-normal"
                                            >
                                                Open a ticket for new mail
                                            </Label>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            disabled={
                                                mailbox.status !== 'connected'
                                            }
                                            onClick={() =>
                                                router.post(
                                                    sync.url(mailbox.id),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Check now
                                        </Button>
                                        <ConfirmDelete
                                            action={destroy.form(mailbox.id)}
                                            trigger="Disconnect"
                                            confirmLabel="Disconnect"
                                            title={`Disconnect ${mailbox.address}?`}
                                            description="MomCRM stops reading this mailbox and can no longer send from it. Tickets it already opened stay."
                                        />
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog open={connecting} onOpenChange={setConnecting}>
                <DialogContent>
                    <DialogTitle>Connect a mailbox</DialogTitle>
                    <DialogDescription>
                        You’ll sign in with Microsoft and let MomCRM read and
                        send mail for the mailbox.
                    </DialogDescription>
                    {/* A plain form: Microsoft's page is a full page load. */}
                    <form
                        action={connect.url()}
                        method="get"
                        className="space-y-4"
                    >
                        <FormField
                            id="mailbox-address"
                            label="Shared mailbox address"
                            hint="Leave empty to connect the mailbox of the account you sign in with. For a shared mailbox, such as support@yourcompany.com, sign in as someone who can open it."
                        >
                            <Input
                                id="mailbox-address"
                                name="address"
                                type="email"
                                placeholder="Optional"
                                autoComplete="off"
                            />
                        </FormField>
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit">Continue to Microsoft</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
