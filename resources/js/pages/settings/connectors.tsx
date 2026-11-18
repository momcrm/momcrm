import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import ConfirmDelete from '@/components/confirm-delete';
import Heading from '@/components/heading';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { connect, destroy } from '@/routes/connectors';
import type { Connector, ConnectorType } from '@/types';

/**
 * Where "Reconnect" sends someone: back to the service for the same account.
 * These are full page loads, because the service's page is not an Inertia page.
 */
function reconnectUrl(connector: Connector): string {
    return connect.url(connector.type, { query: { connector: connector.id } });
}

export default function Connectors({
    connectors,
    types,
}: {
    connectors: Connector[];
    types: ConnectorType[];
}) {
    const [adding, setAdding] = useState(false);
    const canAdd = types.length > 0;
    const canReconnect = (connector: Connector) =>
        types.some((type) => type.value === connector.type);

    return (
        <>
            <Head title="Connectors" />

            <h1 className="sr-only">Connectors</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Connectors"
                        description="Connect the outside accounts MomCRM works through, such as the Microsoft 365 account whose mailbox opens tickets."
                    />
                    {canAdd && (
                        <Button
                            size="sm"
                            className="shrink-0"
                            onClick={() => setAdding(true)}
                        >
                            <Plus />
                            Add connector
                        </Button>
                    )}
                </div>

                {!canAdd && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No kind of connector is set up for this installation
                        yet, so accounts can’t be connected.
                    </p>
                )}

                {canAdd && connectors.length === 0 && (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No accounts connected yet.
                    </p>
                )}

                {connectors.length > 0 && (
                    <ul className="space-y-2">
                        {connectors.map((connector) => (
                            <li
                                key={connector.id}
                                className="flex flex-wrap items-start gap-3 rounded-lg border bg-card p-3 shadow-xs"
                            >
                                <div className="min-w-0 flex-1 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium break-all">
                                            {connector.name}
                                        </span>
                                        {connector.status === 'connected' ? (
                                            <SoftBadge tone="success">
                                                Connected
                                            </SoftBadge>
                                        ) : (
                                            <SoftBadge tone="warning">
                                                Needs reconnecting
                                            </SoftBadge>
                                        )}
                                    </div>
                                    <p className="text-sm break-all text-muted-foreground">
                                        {[
                                            connector.type_label,
                                            connector.name === connector.account
                                                ? null
                                                : connector.account,
                                            connector.capabilities.length > 0
                                                ? `Used for ${connector.capabilities.join(', ').toLowerCase()}`
                                                : null,
                                            connector.shared_mailboxes
                                                ? 'Opens shared mailboxes'
                                                : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    {connector.last_error && (
                                        <p className="text-sm text-destructive">
                                            {connector.last_error}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    {canReconnect(connector) && (
                                        <Button
                                            asChild
                                            variant={
                                                connector.status === 'connected'
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                        >
                                            <a href={reconnectUrl(connector)}>
                                                Reconnect
                                            </a>
                                        </Button>
                                    )}
                                    <ConfirmDelete
                                        action={destroy.form(connector.id)}
                                        trigger="Disconnect"
                                        confirmLabel="Disconnect"
                                        title={`Disconnect ${connector.account}?`}
                                        description="MomCRM forgets how to reach this account, and whatever uses it stops working."
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog open={adding} onOpenChange={setAdding}>
                <DialogContent>
                    <DialogTitle>Add a connector</DialogTitle>
                    <DialogDescription>
                        Choose the kind of account. You’ll sign in to it and let
                        MomCRM work through it.
                    </DialogDescription>
                    <ul className="space-y-2">
                        {types.map((type) => (
                            <li key={type.value}>
                                {/* A plain form: the service's page is a full page load. */}
                                <form
                                    action={connect.url(type.value)}
                                    method="get"
                                    className="space-y-3 rounded-lg border p-3"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {type.label}
                                            </p>
                                            {type.capabilities.length > 0 && (
                                                <p className="text-sm text-muted-foreground">
                                                    Used for{' '}
                                                    {type.capabilities
                                                        .join(', ')
                                                        .toLowerCase()}
                                                </p>
                                            )}
                                        </div>
                                        <Button type="submit" size="sm">
                                            Connect
                                        </Button>
                                    </div>
                                    {type.supports_shared_mailboxes && (
                                        <div className="flex items-start gap-2">
                                            <Checkbox
                                                id={`connector-shared-${type.value}`}
                                                name="shared_mailboxes"
                                                value="1"
                                                className="mt-0.5"
                                            />
                                            <div className="grid gap-0.5">
                                                <Label
                                                    htmlFor={`connector-shared-${type.value}`}
                                                    className="text-sm font-normal"
                                                >
                                                    This account opens shared
                                                    mailboxes
                                                </Label>
                                                <p className="text-xs text-muted-foreground">
                                                    Such as
                                                    support@yourcompany.com.
                                                    Work and school accounts
                                                    only.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </form>
                            </li>
                        ))}
                    </ul>
                </DialogContent>
            </Dialog>
        </>
    );
}
