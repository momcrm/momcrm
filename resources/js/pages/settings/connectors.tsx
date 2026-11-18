import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import {
    Mailbox as MailboxIcon,
    MoreHorizontal,
    RefreshCw,
    Settings2,
    TriangleAlert,
    Unplug,
} from 'lucide-react';
import { useState } from 'react';
import ConnectorLogo from '@/components/connectors/connector-logo';
import MailServerDialog from '@/components/connectors/mail-server-dialog';
import Heading from '@/components/heading';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { connect, destroy } from '@/routes/connectors';
import { edit as editEnvironment } from '@/routes/environment';
import { index as mailboxesIndex } from '@/routes/mailboxes';
import type { Connector, ConnectorType } from '@/types';

/**
 * Where "Reconnect" sends someone: back to the service for the same account.
 * These are full page loads, because the service's page is not an Inertia page.
 */
function reconnectUrl(connector: Connector): string {
    return connect.url(connector.type, { query: { connector: connector.id } });
}

/**
 * "Email" and "Calendar" as "email and calendar".
 */
function uses(capabilities: string[]): string {
    return new Intl.ListFormat('en', { type: 'conjunction' })
        .format(capabilities)
        .toLowerCase();
}

export default function Connectors({
    connectors,
    types,
}: {
    connectors: Connector[];
    types: ConnectorType[];
}) {
    // A gallery, not a form: it wants more room than the other settings pages.
    setLayoutProps({ wide: true });

    const { canManageEmail, canManageEnvironment } = usePage().props;
    // The type being connected that first asks what the sign-in should cover.
    const [choosing, setChoosing] = useState<ConnectorType | null>(null);
    // The mail server form: for a new connector, or for one being changed.
    const [mailServer, setMailServer] = useState<{
        connector: Connector | null;
    } | null>(null);
    const [disconnecting, setDisconnecting] = useState<Connector | null>(null);

    const typeOf = (connector: Connector) =>
        types.find((type) => type.value === connector.type);

    return (
        <>
            <Head title="Connectors" />

            <h1 className="sr-only">Connectors</h1>

            <div className="space-y-8">
                <div className="max-w-xl">
                    <Heading
                        variant="small"
                        title="Connectors"
                        description="Connect the outside accounts MomCRM works through. Each one is used only for what it says, and you can disconnect it at any time."
                    />
                </div>

                {connectors.length > 0 && (
                    <section className="space-y-3">
                        <SectionTitle count={connectors.length}>
                            Connected
                        </SectionTitle>
                        <ul className="space-y-3">
                            {connectors.map((connector) => (
                                <ConnectedCard
                                    key={connector.id}
                                    connector={connector}
                                    canReconnect={
                                        typeOf(connector)?.available === true
                                    }
                                    canManageEmail={canManageEmail === true}
                                    onChange={() =>
                                        setMailServer({ connector })
                                    }
                                    onDisconnect={() =>
                                        setDisconnecting(connector)
                                    }
                                />
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <SectionTitle>
                        {connectors.length > 0
                            ? 'Connect another account'
                            : 'Choose an account to connect'}
                    </SectionTitle>
                    <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {types.map((type) => (
                            <TypeCard
                                key={type.value}
                                type={type}
                                connected={
                                    connectors.filter(
                                        (connector) =>
                                            connector.type === type.value,
                                    ).length
                                }
                                canSetUp={canManageEnvironment === true}
                                onChoose={() => setChoosing(type)}
                                onSetUp={() =>
                                    setMailServer({ connector: null })
                                }
                            />
                        ))}
                    </ul>
                </section>
            </div>

            <Dialog
                open={choosing !== null}
                onOpenChange={(open) => !open && setChoosing(null)}
            >
                <DialogContent>
                    <DialogTitle>Connect {choosing?.label}</DialogTitle>
                    <DialogDescription>
                        You’ll sign in at {choosing?.label} and let MomCRM work
                        through that account.
                    </DialogDescription>
                    {/* A plain form: the service's page is a full page load. */}
                    {choosing && (
                        <form
                            action={connect.url(choosing.value)}
                            method="get"
                            className="space-y-4"
                        >
                            <div className="flex items-start gap-2.5 rounded-lg border p-3">
                                <Checkbox
                                    id="connector-shared"
                                    name="shared_mailboxes"
                                    value="1"
                                    className="mt-0.5"
                                />
                                <div className="grid gap-0.5">
                                    <Label
                                        htmlFor="connector-shared"
                                        className="text-sm font-medium"
                                    >
                                        This account opens shared mailboxes
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Such as support@yourcompany.com. Work
                                        and school accounts only.
                                    </p>
                                </div>
                            </div>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit">
                                    Continue to {choosing.label}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={disconnecting !== null}
                onOpenChange={(open) => !open && setDisconnecting(null)}
            >
                <DialogContent>
                    <DialogTitle>
                        Disconnect {disconnecting?.account}?
                    </DialogTitle>
                    <DialogDescription>
                        {disconnecting && disconnecting.mailboxes.length > 0
                            ? `MomCRM forgets how to reach this account, and removes the mailboxes that go through it: ${disconnecting.mailboxes.join(', ')}. Tickets they opened stay.`
                            : 'MomCRM forgets how to reach this account.'}
                    </DialogDescription>
                    {disconnecting && (
                        <Form
                            {...destroy.form(disconnecting.id)}
                            onSuccess={() => setDisconnecting(null)}
                        >
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            variant="secondary"
                                            type="button"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        variant="destructive"
                                        disabled={processing}
                                    >
                                        Disconnect
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            <MailServerDialog
                open={mailServer !== null}
                onOpenChange={(open) => !open && setMailServer(null)}
                connector={mailServer?.connector ?? null}
            />
        </>
    );
}

function SectionTitle({
    count,
    children,
}: {
    count?: number;
    children: string;
}) {
    return (
        <h2 className="flex items-center gap-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
            {children}
            {count !== undefined && (
                <span className="rounded-full bg-muted px-1.5 py-px text-[10px] text-muted-foreground tabular-nums">
                    {count}
                </span>
            )}
        </h2>
    );
}

/**
 * An account that is connected: what it is, how it is doing, what goes
 * through it, and the things that can be done to it.
 */
function ConnectedCard({
    connector,
    canReconnect,
    canManageEmail,
    onChange,
    onDisconnect,
}: {
    connector: Connector;
    /** Whether this installation can still sign in to this kind of account. */
    canReconnect: boolean;
    canManageEmail: boolean;
    onChange: () => void;
    onDisconnect: () => void;
}) {
    const isWorking = connector.status === 'connected';
    const fix = connector.uses_sign_in ? 'Reconnect' : 'Update password';

    return (
        <li className="rounded-xl bg-card shadow-card">
            <div className="flex items-start gap-3 p-4">
                <ConnectorLogo type={connector.type} />
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <h3 className="truncate font-medium">
                            {connector.name}
                        </h3>
                        <SoftBadge
                            tone={isWorking ? 'success' : 'warning'}
                            className="gap-1.5"
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'size-1.5 rounded-full',
                                    isWorking ? 'bg-success' : 'bg-warning',
                                )}
                            />
                            {isWorking ? 'Connected' : 'Needs reconnecting'}
                        </SoftBadge>
                    </div>
                    <p className="truncate text-sm text-muted-foreground">
                        {[
                            connector.type_label,
                            connector.name === connector.account
                                ? null
                                : connector.account,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>
                <div className="flex shrink-0 items-center gap-1">
                    {!isWorking &&
                        (connector.uses_sign_in ? (
                            canReconnect && (
                                <Button asChild size="sm">
                                    <a href={reconnectUrl(connector)}>{fix}</a>
                                </Button>
                            )
                        ) : (
                            <Button size="sm" onClick={onChange}>
                                {fix}
                            </Button>
                        ))}
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            aria-label={`More for ${connector.account}`}
                            className="flex size-7 items-center justify-center rounded-md text-soft-foreground outline-none hover:bg-accent hover:text-foreground data-[state=open]:bg-accent"
                        >
                            <MoreHorizontal className="size-4" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-52">
                            {connector.uses_sign_in && canReconnect && (
                                <DropdownMenuItem asChild>
                                    <a href={reconnectUrl(connector)}>
                                        <RefreshCw />
                                        Reconnect
                                    </a>
                                </DropdownMenuItem>
                            )}
                            {!connector.uses_sign_in && (
                                <DropdownMenuItem onSelect={onChange}>
                                    <Settings2 />
                                    Change servers or password
                                </DropdownMenuItem>
                            )}
                            {canManageEmail && (
                                <DropdownMenuItem asChild>
                                    <Link href={mailboxesIndex()}>
                                        <MailboxIcon />
                                        Manage mailboxes
                                    </Link>
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={onDisconnect}
                            >
                                <Unplug />
                                Disconnect
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            {!isWorking && connector.last_error && (
                <p className="mx-4 mb-4 flex items-start gap-2 rounded-lg bg-warning/10 px-3 py-2 text-sm">
                    <TriangleAlert
                        aria-hidden
                        className="mt-0.5 size-4 shrink-0 text-warning"
                    />
                    <span>
                        {connector.last_error}
                        {connector.uses_sign_in &&
                            !canReconnect &&
                            ` This installation is no longer set up to sign in to ${connector.type_label}.`}
                    </span>
                </p>
            )}

            <dl className="grid gap-x-6 gap-y-3 border-t px-4 py-3 text-sm sm:grid-cols-[1fr_1.5fr_1fr]">
                <Detail label="Used for">
                    {connector.capabilities.length > 0
                        ? uses(connector.capabilities).replace(/^./, (first) =>
                              first.toUpperCase(),
                          )
                        : 'Nothing yet'}
                    {connector.shared_mailboxes && ', with shared mailboxes'}
                </Detail>
                <Detail label="Mailboxes">
                    {connector.mailboxes.length > 0 ? (
                        <ul className="flex flex-wrap gap-1">
                            {connector.mailboxes.map((address) => (
                                <li
                                    key={address}
                                    className="max-w-full truncate rounded-md bg-muted px-1.5 py-0.5 text-xs text-foreground"
                                >
                                    {address}
                                </li>
                            ))}
                        </ul>
                    ) : canManageEmail ? (
                        <Link
                            href={mailboxesIndex()}
                            className="font-medium underline underline-offset-4"
                        >
                            Add a mailbox
                        </Link>
                    ) : (
                        'None yet'
                    )}
                </Detail>
                <Detail label="Connected">
                    {connector.connected_at
                        ? timeAgo(connector.connected_at)
                        : 'Unknown'}
                    {connector.connected_by && ` by ${connector.connected_by}`}
                </Detail>
            </dl>
        </li>
    );
}

function Detail({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-subtle-foreground">{label}</dt>
            <dd className="mt-0.5">{children}</dd>
        </div>
    );
}

/**
 * A kind of account that can be connected: what it is, what it is used for,
 * and the way in. One this installation is not set up for says so.
 */
function TypeCard({
    type,
    connected,
    canSetUp,
    onChoose,
    onSetUp,
}: {
    type: ConnectorType;
    /** How many accounts of this kind are connected already. */
    connected: number;
    /** Whether this person can add the keys a type is missing. */
    canSetUp: boolean;
    onChoose: () => void;
    onSetUp: () => void;
}) {
    return (
        <li className="flex flex-col gap-3 rounded-xl bg-card p-4 shadow-card">
            <div className="flex items-start justify-between gap-2">
                <ConnectorLogo
                    type={type.value}
                    className={cn(!type.available && 'opacity-50 grayscale')}
                />
                {connected > 0 && (
                    <SoftBadge tone="neutral">{connected} connected</SoftBadge>
                )}
            </div>
            <div className="space-y-0.5">
                <h3 className="font-medium">{type.label}</h3>
                <p className="text-sm text-muted-foreground">
                    {type.description}
                </p>
            </div>
            <div className="mt-auto flex items-center justify-between gap-3 pt-1">
                <p className="text-xs text-subtle-foreground">
                    {type.capabilities.length > 0 &&
                        `Used for ${uses(type.capabilities)}`}
                </p>
                {!type.available ? (
                    canSetUp ? (
                        <Button asChild size="sm" variant="outline">
                            <Link href={editEnvironment()}>Add its keys</Link>
                        </Button>
                    ) : (
                        <span className="text-xs text-muted-foreground">
                            Not set up here
                        </span>
                    )
                ) : !type.uses_sign_in ? (
                    <Button size="sm" variant="outline" onClick={onSetUp}>
                        Set up
                    </Button>
                ) : type.supports_shared_mailboxes ? (
                    <Button size="sm" variant="outline" onClick={onChoose}>
                        Connect
                    </Button>
                ) : (
                    // A plain link: the service's page is a full page load.
                    <Button asChild size="sm" variant="outline">
                        <a href={connect.url(type.value)}>Connect</a>
                    </Button>
                )}
            </div>
        </li>
    );
}
