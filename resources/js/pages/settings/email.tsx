import { Form, Head, Link, router, usePage } from '@inertiajs/react';
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
import { index as connectorsIndex } from '@/routes/connectors';
import { destroy, store, sync, update } from '@/routes/mailboxes';
import type { Mailbox, MailConnector } from '@/types';

type GroupOption = { id: string; name: string };

export default function Email({
    mailboxes,
    groups,
    connectors,
}: {
    mailboxes: Mailbox[];
    groups: GroupOption[];
    connectors: MailConnector[];
}) {
    const { canManageConnectors } = usePage().props;
    const [adding, setAdding] = useState(false);
    const [connectorId, setConnectorId] = useState(connectors[0]?.id ?? '');
    const connector = connectors.find((item) => item.id === connectorId);
    const canAdd = connectors.length > 0;

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
                        description="Mail sent to these mailboxes opens tickets. A mailbox is read and sent through a connected account, and only mail that arrives after it is added is read."
                    />
                    {canAdd && (
                        <Button
                            size="sm"
                            className="shrink-0"
                            onClick={() => setAdding(true)}
                        >
                            <Plus />
                            Add mailbox
                        </Button>
                    )}
                </div>

                {!canAdd && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Connect an account that can do email first, then add its
                        mailbox here.{' '}
                        {canManageConnectors && (
                            <Link
                                href={connectorsIndex()}
                                className="font-medium text-foreground underline underline-offset-4"
                            >
                                Go to Connectors
                            </Link>
                        )}
                    </p>
                )}

                {canAdd && mailboxes.length === 0 && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No mailboxes added yet.
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
                                                mailbox.connector_type
                                                    ? `Through ${mailbox.connector_type} (${mailbox.connector_name})`
                                                    : null,
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
                                    {mailbox.status !== 'connected' &&
                                        canManageConnectors && (
                                            <Button asChild size="sm">
                                                <Link href={connectorsIndex()}>
                                                    Reconnect in Connectors
                                                </Link>
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
                                            trigger="Remove"
                                            confirmLabel="Remove"
                                            title={`Remove ${mailbox.address}?`}
                                            description="MomCRM stops reading this mailbox and can no longer send from it. The account it goes through stays connected, and tickets it already opened stay."
                                        />
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog open={adding} onOpenChange={setAdding}>
                <DialogContent>
                    <DialogTitle>Add a mailbox</DialogTitle>
                    <DialogDescription>
                        Choose the connected account to read and send the
                        mailbox through.
                    </DialogDescription>
                    <Form
                        {...store.form()}
                        className="space-y-4"
                        resetOnSuccess
                        onSuccess={() => setAdding(false)}
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    id="mailbox-connector"
                                    label="Connected account"
                                    error={errors.connector_id}
                                >
                                    <NativeSelect
                                        id="mailbox-connector"
                                        name="connector_id"
                                        value={connectorId}
                                        onChange={(event) =>
                                            setConnectorId(event.target.value)
                                        }
                                    >
                                        {connectors.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.has_own_mailbox
                                                    ? item.account
                                                    : item.name}{' '}
                                                ({item.type_label})
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                {connector?.shared_mailboxes ? (
                                    <FormField
                                        id="mailbox-address"
                                        label={
                                            connector.has_own_mailbox
                                                ? 'Shared mailbox address'
                                                : 'Address'
                                        }
                                        hint={
                                            connector.has_own_mailbox
                                                ? `Leave empty to add ${connector.account} itself. For a shared mailbox, such as support@yourcompany.com, the account must be able to open it.`
                                                : `An address on one of ${connector.name}'s domains, such as support@yourcompany.com. Mail sent to it becomes tickets.`
                                        }
                                        error={errors.address}
                                    >
                                        <Input
                                            id="mailbox-address"
                                            name="address"
                                            type="email"
                                            placeholder={
                                                connector.has_own_mailbox
                                                    ? 'Optional'
                                                    : 'support@yourcompany.com'
                                            }
                                            required={
                                                !connector.has_own_mailbox
                                            }
                                            autoComplete="off"
                                        />
                                    </FormField>
                                ) : (
                                    <InputError message={errors.address} />
                                )}
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            variant="secondary"
                                            type="button"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        Add mailbox
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
