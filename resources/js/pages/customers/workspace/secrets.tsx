import { Head, router, useHttp } from '@inertiajs/react';
import {
    Copy,
    Eye,
    EyeOff,
    KeyRound,
    Plus,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { timeAgo } from '@/lib/format';
import { visitWithQuery } from '@/lib/query';
import { totpCode } from '@/lib/totp';
import { secrets as secretsTab } from '@/routes/customers';
import { destroy, reveal, store, update } from '@/routes/secrets';
import type {
    Abilities,
    CustomerOption,
    CustomerWorkspace,
    SecretItem,
    SecretPair,
} from '@/types';

type SecretRow = SecretItem & { can: Abilities };

const RELOAD = ['secrets', 'filters', 'workspace'];

async function copy(text: string, what: string) {
    try {
        await navigator.clipboard.writeText(text);
        toast.success(`${what} copied.`);
    } catch {
        toast.error(`Couldn't copy the ${what.toLowerCase()}.`);
    }
}

/**
 * Fetches one secret's values on demand. The server logs every reveal.
 */
function useReveal() {
    const http = useHttp<Record<string, never>, { values: SecretPair[] }>();

    return (secretId: string): Promise<SecretPair[] | null> =>
        http
            .post(reveal.url(secretId))
            .then((response) => response.values)
            .catch(() => {
                toast.error("You can't reveal this secret.");

                return null;
            });
}

function isTotp(key: string): boolean {
    return ['totp', 'otp', '2fa', 'mfa'].includes(key.trim().toLowerCase());
}

/**
 * A secret's values, masked until revealed, each with a copy button. A
 * `totp` key also copies its current one-time code.
 */
function SecretDialog({
    secret,
    assetName,
    onEdit,
    onClose,
}: {
    secret: SecretRow;
    assetName: string | undefined;
    onEdit: (values: SecretPair[] | null) => void;
    onClose: () => void;
}) {
    const revealValues = useReveal();
    const [values, setValues] = useState<SecretPair[] | null>(null);
    const [isRevealing, setIsRevealing] = useState(false);

    const onReveal = async () => {
        setIsRevealing(true);
        setValues(await revealValues(secret.id));
        setIsRevealing(false);
    };

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <KeyRound className="size-4 text-subtle-foreground" />
                        {secret.title}
                    </DialogTitle>
                    <DialogDescription>
                        {[secret.description, assetName && `For ${assetName}`]
                            .filter(Boolean)
                            .join(' · ') || 'Saved for this customer.'}
                    </DialogDescription>
                </DialogHeader>

                {values ? (
                    <dl className="divide-y rounded-lg border">
                        {values.map((pair) => (
                            <div
                                key={pair.key}
                                className="flex items-center gap-3 px-3 py-2"
                            >
                                <dt className="w-28 shrink-0 truncate text-xs text-muted-foreground">
                                    {pair.key}
                                </dt>
                                <dd className="min-w-0 flex-1 font-mono text-[13px] break-all">
                                    {pair.value}
                                </dd>
                                {isTotp(pair.key) && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={async () => {
                                            const code = await totpCode(
                                                pair.value,
                                            );

                                            if (code) {
                                                void copy(code, 'Code');
                                            } else {
                                                toast.error(
                                                    "This isn't a valid TOTP secret.",
                                                );
                                            }
                                        }}
                                    >
                                        <ShieldCheck className="size-4" />
                                        Copy code
                                    </Button>
                                )}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Copy ${pair.key}`}
                                    onClick={() =>
                                        void copy(pair.value, pair.key)
                                    }
                                >
                                    <Copy className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed p-6 text-center">
                        <p className="font-mono tracking-widest text-subtle-foreground">
                            ••••••••••••
                        </p>
                        {secret.can.reveal ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isRevealing}
                                onClick={() => void onReveal()}
                            >
                                <Eye className="size-4" />
                                Reveal
                            </Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Your team's access rules don't let you reveal
                                secrets.
                            </p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            Each reveal is recorded in the audit log.
                        </p>
                    </div>
                )}

                <DialogFooter className="sm:justify-between">
                    <div>
                        {secret.can.delete && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="text-destructive"
                                onClick={() =>
                                    router.delete(destroy.url(secret.id), {
                                        only: RELOAD,
                                        preserveScroll: true,
                                        onSuccess: onClose,
                                    })
                                }
                            >
                                <Trash2 className="size-4" />
                                Delete
                            </Button>
                        )}
                    </div>
                    <div className="flex gap-2">
                        {values && (
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setValues(null)}
                            >
                                <EyeOff className="size-4" />
                                Hide
                            </Button>
                        )}
                        {secret.can.update && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onEdit(values)}
                            >
                                Edit
                            </Button>
                        )}
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Makes or edits a secret. When editing, its values are only sent once they
 * have been revealed into the form, so a rename never needs them.
 */
function SecretForm({
    customerId,
    secret,
    initialValues,
    assets,
    defaultAssetId,
    canReveal,
    onClose,
}: {
    customerId: string;
    secret: SecretRow | null;
    initialValues: SecretPair[] | null;
    assets: CustomerOption[];
    defaultAssetId: string | null;
    canReveal: boolean;
    onClose: () => void;
}) {
    const revealValues = useReveal();
    const [title, setTitle] = useState(secret?.title ?? '');
    const [description, setDescription] = useState(secret?.description ?? '');
    const [assetId, setAssetId] = useState(
        secret ? (secret.asset_id ?? '') : (defaultAssetId ?? ''),
    );
    const [values, setValues] = useState<SecretPair[] | null>(
        secret
            ? initialValues
            : [
                  { key: 'username', value: '' },
                  { key: 'password', value: '' },
              ],
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);

    const setPair = (index: number, changes: Partial<SecretPair>) =>
        setValues((current) =>
            (current ?? []).map((pair, at) =>
                at === index ? { ...pair, ...changes } : pair,
            ),
        );

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        const data = {
            title,
            description: description || null,
            asset_id: assetId || null,
            ...(values ? { values } : {}),
        };
        const options = {
            only: RELOAD,
            preserveScroll: true,
            onStart: () => setIsSaving(true),
            onFinish: () => setIsSaving(false),
            onError: (next: Record<string, string>) => setErrors(next),
            onSuccess: onClose,
        };

        if (secret) {
            router.put(update.url(secret.id), data, options);
        } else {
            router.post(store.url(customerId), data, options);
        }
    };

    const valueErrors = Object.entries(errors)
        .filter(([field]) => field.startsWith('values'))
        .map(([, message]) => message);

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <form onSubmit={onSubmit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {secret ? `Edit ${secret.title}` : 'New secret'}
                        </DialogTitle>
                        <DialogDescription>
                            Values are encrypted, and hidden until someone
                            reveals them.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField id="title" label="Title" error={errors.title}>
                        <Input
                            id="title"
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                            placeholder="Firewall admin"
                            required
                            autoFocus
                        />
                    </FormField>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="description"
                            label="Description"
                            error={errors.description}
                        >
                            <Input
                                id="description"
                                value={description}
                                onChange={(event) =>
                                    setDescription(event.target.value)
                                }
                                maxLength={255}
                            />
                        </FormField>
                        <FormField
                            id="asset_id"
                            label="Asset"
                            error={errors.asset_id}
                        >
                            <NativeSelect
                                id="asset_id"
                                value={assetId}
                                onChange={(event) =>
                                    setAssetId(event.target.value)
                                }
                            >
                                <option value="">The whole customer</option>
                                {assets.map((asset) => (
                                    <option key={asset.id} value={asset.id}>
                                        {asset.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                    </div>

                    <fieldset className="space-y-2">
                        <legend className="mb-1 text-sm font-medium">
                            Values
                        </legend>
                        {values ? (
                            <>
                                {values.map((pair, index) => (
                                    <div key={index} className="flex gap-2">
                                        <Input
                                            aria-label={`Key ${index + 1}`}
                                            value={pair.key}
                                            placeholder="Key"
                                            className="w-36 shrink-0"
                                            onChange={(event) =>
                                                setPair(index, {
                                                    key: event.target.value,
                                                })
                                            }
                                        />
                                        <Input
                                            aria-label={`Value ${index + 1}`}
                                            value={pair.value}
                                            placeholder="Value"
                                            className="font-mono"
                                            autoComplete="off"
                                            onChange={(event) =>
                                                setPair(index, {
                                                    value: event.target.value,
                                                })
                                            }
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove ${pair.key || 'row'}`}
                                            disabled={values.length === 1}
                                            onClick={() =>
                                                setValues(
                                                    values.filter(
                                                        (_, at) => at !== index,
                                                    ),
                                                )
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ))}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        setValues([
                                            ...values,
                                            { key: '', value: '' },
                                        ])
                                    }
                                >
                                    <Plus className="size-4" />
                                    Add value
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    Name a key “totp” to copy one-time codes
                                    from it.
                                </p>
                            </>
                        ) : (
                            <div className="flex items-center justify-between gap-3 rounded-lg border border-dashed px-3 py-2.5 text-sm text-muted-foreground">
                                The values stay as they are.
                                {canReveal && secret && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={async () =>
                                            setValues(
                                                await revealValues(secret.id),
                                            )
                                        }
                                    >
                                        <Eye className="size-4" />
                                        Reveal to change
                                    </Button>
                                )}
                            </div>
                        )}
                        {valueErrors.length > 0 && (
                            <p className="text-sm text-destructive">
                                {valueErrors[0]}
                            </p>
                        )}
                    </fieldset>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isSaving}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CustomerSecrets({
    workspace,
    secrets,
    assets,
    filters,
    secretCan,
}: {
    workspace: CustomerWorkspace;
    secrets: SecretRow[];
    assets: CustomerOption[];
    filters: { asset: string | null; search: string | null };
    secretCan: Abilities;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Secrets',
        href: secretsTab(customer.id),
    });
    const [open, setOpen] = useState<SecretRow | null>(null);
    const [editing, setEditing] = useState<{
        secret: SecretRow | null;
        values: SecretPair[] | null;
    } | null>(null);
    const assetName = (id: string | null) =>
        assets.find((asset) => asset.id === id)?.name;
    const isFiltered = Boolean(filters.asset || filters.search);
    const add = secretCan.create && (
        <Button
            size="sm"
            variant="outline"
            onClick={() => setEditing({ secret: null, values: null })}
        >
            New secret
        </Button>
    );

    return (
        <>
            <Head title={`${customer.name} · Secrets`} />
            <TabHeader
                title="Secrets"
                description="Logins, keys and codes for this customer. Values stay encrypted until revealed."
                action={add}
            />

            {(secrets.length > 0 || isFiltered) && (
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <SearchInput
                        value={filters.search}
                        placeholder="Search secrets"
                        only={RELOAD}
                        size="sm"
                    />
                    {assets.length > 0 && (
                        <NativeSelect
                            aria-label="Asset"
                            value={filters.asset ?? ''}
                            onChange={(event) =>
                                visitWithQuery(
                                    { asset: event.target.value },
                                    { only: RELOAD },
                                )
                            }
                            className="h-8 w-auto text-[13px]"
                        >
                            <option value="">Any asset</option>
                            {assets.map((asset) => (
                                <option key={asset.id} value={asset.id}>
                                    {asset.name}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                </div>
            )}

            {secrets.length > 0 ? (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {secrets.map((secret) => (
                        <li key={secret.id}>
                            <button
                                type="button"
                                onClick={() => setOpen(secret)}
                                className="flex min-h-11 w-full items-center gap-3 px-3 py-1.5 text-left text-sm hover:bg-accent/50"
                            >
                                <KeyRound className="size-4 shrink-0 text-subtle-foreground" />
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate font-medium">
                                        {secret.title}
                                    </span>
                                    {secret.description && (
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {secret.description}
                                        </span>
                                    )}
                                </span>
                                {secret.asset_id && (
                                    <span className="hidden truncate text-xs text-muted-foreground sm:block">
                                        {assetName(secret.asset_id)}
                                    </span>
                                )}
                                <span className="hidden w-24 shrink-0 text-right text-xs text-subtle-foreground md:block">
                                    {timeAgo(secret.updated_at)}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            ) : isFiltered ? (
                <EmptyState
                    title="No matching secrets"
                    description="Try another search, or any asset."
                />
            ) : (
                <EmptyState
                    title="No secrets yet"
                    description={`Keep ${customer.name}'s admin logins, Wi-Fi keys and recovery codes here, encrypted and logged.`}
                    action={add}
                />
            )}

            {open && (
                <SecretDialog
                    key={open.id}
                    secret={open}
                    assetName={assetName(open.asset_id)}
                    onClose={() => setOpen(null)}
                    onEdit={(values) => {
                        setEditing({ secret: open, values });
                        setOpen(null);
                    }}
                />
            )}
            {editing && (
                <SecretForm
                    customerId={customer.id}
                    secret={editing.secret}
                    initialValues={editing.values}
                    assets={assets}
                    defaultAssetId={filters.asset}
                    canReveal={Boolean(editing.secret?.can.reveal)}
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}
