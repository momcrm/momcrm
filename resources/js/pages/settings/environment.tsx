import { Form, Head, useHttp } from '@inertiajs/react';
import {
    Eye,
    EyeOff,
    Lock,
    Pencil,
    Plus,
    RotateCw,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import FormField from '@/components/form-field';
import Heading from '@/components/heading';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { timeAgo } from '@/lib/format';
import { apply } from '@/routes/environment';
import { destroy, reveal, store, update } from '@/routes/environment/variables';

type EnvironmentVariable = {
    key: string;
    /** Null for a secret or locked variable: reveal it to read it. */
    value: string | null;
    is_secret: boolean;
    /** `APP_KEY`: never shown, revealed, changed or removed. */
    is_locked: boolean;
    is_empty: boolean;
};

type EnvironmentMeta = {
    /** Deploys cache the config, and then nothing reads the file until it is applied. */
    config_cached: boolean;
    modified_at: string | null;
    changed_at: string | null;
    applied_at: string | null;
    /** A change made on this page has not been applied. Edits over SSH aren't tracked. */
    has_pending_changes: boolean;
};

const keyPattern = /^[A-Z][A-Z0-9_]*$/;

/**
 * Reads one value, secrets included. The value stays in the component that
 * asked and never reaches the page's props.
 */
function useReveal() {
    const http = useHttp<Record<string, never>, { value: string }>();

    return (key: string): Promise<string | null> =>
        http
            .post(reveal.url(key))
            .then((response) => response.value)
            .catch(() => {
                toast.error(`Couldn't reveal ${key}.`);

                return null;
            });
}

/**
 * A variable's value. A secret shows as dots until revealed; the revealed
 * value lives only in this row, and hides again once the value changes.
 */
function VariableValue({ variable }: { variable: EnvironmentVariable }) {
    const revealValue = useReveal();
    const [revealed, setRevealed] = useState<string | null>(null);
    const [isRevealing, setIsRevealing] = useState(false);

    if (variable.is_locked) {
        return (
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Lock className="size-3.5" />
                Hidden
            </span>
        );
    }

    if (variable.is_empty) {
        return (
            <span className="text-xs text-muted-foreground italic">empty</span>
        );
    }

    const shown = variable.is_secret ? revealed : variable.value;

    const toggle = async () => {
        if (revealed !== null) {
            setRevealed(null);

            return;
        }

        setIsRevealing(true);
        setRevealed(await revealValue(variable.key));
        setIsRevealing(false);
    };

    return (
        <span className="flex min-w-0 items-center gap-1">
            {shown === null ? (
                <span
                    className="font-mono text-xs tracking-widest text-muted-foreground select-none"
                    aria-label="Hidden value"
                >
                    ••••••••••••
                </span>
            ) : (
                <span className="truncate font-mono text-xs" title={shown}>
                    {shown}
                </span>
            )}
            {variable.is_secret && (
                <Button
                    variant="ghost"
                    size="icon-xs"
                    className="shrink-0"
                    aria-label={
                        revealed === null
                            ? `Reveal ${variable.key}`
                            : `Hide ${variable.key}`
                    }
                    disabled={isRevealing}
                    onClick={() => void toggle()}
                >
                    {isRevealing ? (
                        <Spinner />
                    ) : revealed === null ? (
                        <Eye />
                    ) : (
                        <EyeOff />
                    )}
                </Button>
            )}
        </span>
    );
}

/**
 * Adds a variable, or edits one when `variable` is given. Editing a secret
 * reveals its current value first, so it can be changed rather than retyped.
 */
function VariableDialog({
    variable,
    onClose,
}: {
    variable: EnvironmentVariable | null;
    onClose: () => void;
}) {
    const revealValue = useReveal();
    const [key, setKey] = useState('');
    const [value, setValue] = useState(variable?.value ?? '');
    const needsReveal = Boolean(variable?.is_secret && !variable.is_empty);
    const [isRevealing, setIsRevealing] = useState(needsReveal);

    useEffect(() => {
        if (!variable || !needsReveal) {
            return;
        }

        void revealValue(variable.key).then((current) => {
            setValue(current ?? '');
            setIsRevealing(false);
        });
        // Read once, when the dialog opens.
    }, []);

    const keyIsValid = keyPattern.test(key);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>
                    {variable ? `Edit ${variable.key}` : 'Add a variable'}
                </DialogTitle>
                <DialogDescription>
                    Saved straight to the{' '}
                    <code className="font-mono">.env</code>, which is backed up
                    first. Nothing reads it until you apply the environment.
                </DialogDescription>
                <Form
                    {...(variable ? update.form(variable.key) : store.form())}
                    transform={() => (variable ? { value } : { key, value })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-4"
                >
                    {({ errors, processing, submit }) => (
                        <>
                            {!variable && (
                                <FormField
                                    id="environment-variable-key"
                                    label="Key"
                                    error={
                                        errors.key ??
                                        (key && !keyIsValid
                                            ? 'Use upper case letters, digits and underscores, starting with a letter.'
                                            : undefined)
                                    }
                                >
                                    <Input
                                        id="environment-variable-key"
                                        value={key}
                                        onChange={(event) =>
                                            setKey(
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        className="font-mono"
                                        placeholder="MAIL_FROM_NAME"
                                        autoComplete="off"
                                        maxLength={128}
                                        required
                                        autoFocus
                                    />
                                </FormField>
                            )}
                            <FormField
                                id="environment-variable-value"
                                label="Value"
                                hint="Type it unquoted; it is quoted as needed. ${OTHER_KEY} refers to another variable. Shift+Enter for a new line."
                                error={errors.value}
                            >
                                {isRevealing ? (
                                    <p className="flex h-18 items-center gap-2 text-sm text-muted-foreground">
                                        <Spinner />
                                        Reading the current value…
                                    </p>
                                ) : (
                                    <Textarea
                                        id="environment-variable-value"
                                        value={value}
                                        onChange={(event) =>
                                            setValue(event.target.value)
                                        }
                                        onKeyDown={(event) => {
                                            if (
                                                event.key === 'Enter' &&
                                                !event.shiftKey
                                            ) {
                                                event.preventDefault();
                                                submit();
                                            }
                                        }}
                                        className="font-mono text-xs"
                                        placeholder="Leave empty for an empty value"
                                        autoComplete="off"
                                        spellCheck={false}
                                        autoFocus={Boolean(variable)}
                                    />
                                )}
                            </FormField>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        isRevealing ||
                                        (!variable && !keyIsValid)
                                    }
                                >
                                    {variable ? 'Save' : 'Add variable'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RemoveDialog({
    variable,
    onClose,
}: {
    variable: EnvironmentVariable;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogTitle>Remove {variable.key}?</DialogTitle>
                <DialogDescription>
                    This removes it from the{' '}
                    <code className="font-mono">.env</code>. Once applied, its
                    config falls back to the default. The old file is backed up
                    on the server.
                </DialogDescription>
                <Form
                    {...destroy.form(variable.key)}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button variant="destructive" disabled={processing}>
                                Remove
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Makes the running app read the file again: rebuilds the cached config (when
 * there is one) and restarts the queue workers after their current job.
 */
function ApplyDialog({
    meta,
    onClose,
}: {
    meta: EnvironmentMeta;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogTitle>Apply the environment?</DialogTitle>
                <div className="space-y-2 text-sm text-muted-foreground">
                    <p>
                        {meta.config_cached
                            ? "The config is cached, so it's rebuilt from the file now. Every request after that uses the new values."
                            : "The config isn't cached, so web requests already read the file."}
                    </p>
                    <p>
                        Queue workers restart after their current job.
                        Long-running servers keep their old values until they
                        are restarted on the server.
                    </p>
                    <p>
                        A wrong database or cache value can take the app down,
                        and with it this page. Fixing that takes SSH, where the
                        previous files are kept in{' '}
                        <code className="font-mono">
                            storage/app/private/environment-backups
                        </code>
                        .
                    </p>
                </div>
                <Form
                    {...apply.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button disabled={processing}>Apply</Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function Environment({
    variables,
    meta,
}: {
    variables: EnvironmentVariable[];
    meta: EnvironmentMeta;
}) {
    const [search, setSearch] = useState('');
    const [editing, setEditing] = useState<EnvironmentVariable | null>(null);
    const [adding, setAdding] = useState(false);
    const [removing, setRemoving] = useState<EnvironmentVariable | null>(null);
    const [applying, setApplying] = useState(false);

    // Secrets are searched by name only; their values never reach the page.
    const shown = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term === ''
            ? variables
            : variables.filter((variable) =>
                  `${variable.key} ${variable.value ?? ''}`
                      .toLowerCase()
                      .includes(term),
              );
    }, [search, variables]);

    return (
        <>
            <Head title="Environment" />

            <h1 className="sr-only">Environment</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1.5">
                        <Heading
                            variant="small"
                            title="Environment"
                            description="This installation's .env, in file order. Changes are written to the file straight away, but only reach the running app once applied."
                        />
                        {meta.has_pending_changes ? (
                            <SoftBadge tone="warning">
                                Not applied
                                {meta.changed_at &&
                                    ` · changed ${timeAgo(meta.changed_at)}`}
                            </SoftBadge>
                        ) : (
                            meta.applied_at && (
                                <p className="text-xs text-muted-foreground">
                                    Applied {timeAgo(meta.applied_at)}
                                </p>
                            )
                        )}
                    </div>
                    <div className="flex shrink-0 gap-2">
                        <Button
                            size="sm"
                            variant={
                                meta.has_pending_changes ? 'default' : 'outline'
                            }
                            onClick={() => setApplying(true)}
                        >
                            <RotateCw />
                            {meta.has_pending_changes
                                ? 'Apply changes'
                                : 'Apply'}
                        </Button>
                        <Button size="sm" onClick={() => setAdding(true)}>
                            <Plus />
                            Add variable
                        </Button>
                    </div>
                </div>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search variables"
                        aria-label="Search variables"
                        className="pl-8"
                    />
                </div>

                {shown.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        {variables.length === 0
                            ? 'The .env file is empty or missing.'
                            : 'No variables match your search.'}
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {shown.map((variable) => (
                            <li
                                key={variable.key}
                                className="flex min-h-11 items-center gap-3 px-3 py-1.5"
                            >
                                <span
                                    className="w-56 shrink-0 truncate font-mono text-xs font-medium"
                                    title={variable.key}
                                >
                                    {variable.key}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <VariableValue
                                        // Keyed by the value, so a revealed secret hides again once it changes.
                                        key={`${variable.value ?? ''}:${variable.is_empty}`}
                                        variable={variable}
                                    />
                                </span>
                                {!variable.is_locked && (
                                    <span className="flex shrink-0 items-center gap-0.5">
                                        <Button
                                            variant="ghost"
                                            size="icon-xs"
                                            aria-label={`Edit ${variable.key}`}
                                            onClick={() => setEditing(variable)}
                                        >
                                            <Pencil />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-xs"
                                            className="text-destructive hover:text-destructive"
                                            aria-label={`Remove ${variable.key}`}
                                            onClick={() =>
                                                setRemoving(variable)
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {(adding || editing) && (
                <VariableDialog
                    variable={editing}
                    onClose={() => {
                        setAdding(false);
                        setEditing(null);
                    }}
                />
            )}

            {removing && (
                <RemoveDialog
                    variable={removing}
                    onClose={() => setRemoving(null)}
                />
            )}

            {applying && (
                <ApplyDialog meta={meta} onClose={() => setApplying(false)} />
            )}
        </>
    );
}
