import { Form, Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    ChevronRight,
    ListPlus,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    ObjectIcon,
    objectIcons,
} from '@/components/custom-objects/object-icon';
import FormField from '@/components/form-field';
import Heading from '@/components/heading';
import HuePicker from '@/components/hue-picker';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import {
    archive,
    destroy,
    fields,
    reorder,
    restore,
    store,
    update,
} from '@/routes/custom-objects';
import type { CustomObject } from '@/types';

/** A kind of record built into the app. */
type StandardObject = {
    key: string;
    label: string;
    icon: string;
    /** How many fields the app gives it. */
    fields_count: number;
    custom_fields_count: number;
};

type CustomObjectRow = CustomObject & { custom_fields_count: number };

function fieldCount(count: number, noun = 'custom field'): string {
    return `${count} ${noun}${count === 1 ? '' : 's'}`;
}

const saveOptions = {
    preserveScroll: true,
    onError: (errors: Record<string, string>) => {
        const message = Object.values(errors)[0];

        if (message) {
            toast.error(message);
        }
    },
};

/**
 * Every kind of record the team works with: the ones built into the app,
 * then the ones the team has defined for itself, such as "Vehicles", in the
 * order the sidebar lists them. Each opens its fields.
 */
export default function CustomObjects({
    standardObjects,
    objects,
}: {
    standardObjects: StandardObject[];
    objects: CustomObjectRow[];
}) {
    const [creating, setCreating] = useState(false);
    const live = objects.filter((object) => !object.is_archived);
    const archived = objects.filter((object) => object.is_archived);

    const move = (object: CustomObjectRow, direction: -1 | 1) => {
        const ids = live.map((item) => item.id);
        const from = ids.indexOf(object.id);
        const to = from + direction;

        if (to < 0 || to >= ids.length) {
            return;
        }

        [ids[from], ids[to]] = [ids[to], ids[from]];

        router.put(reorder.url(), { ids }, saveOptions);
    };

    return (
        <>
            <Head title="Objects" />

            <h1 className="sr-only">Objects</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Objects"
                    description="Every kind of record your team works with. Open one to see its fields and add your own."
                />

                <section className="space-y-2">
                    <div>
                        <h2 className="text-sm font-medium">Built in</h2>
                        <p className="text-xs text-muted-foreground">
                            Part of MomCRM. They can&apos;t be renamed or
                            removed, but each takes your own fields.
                        </p>
                    </div>
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {standardObjects.map((object) => (
                            <li key={object.key}>
                                <Link
                                    href={fields(object.key)}
                                    className="flex min-h-12 items-center gap-3 px-3 py-1.5 hover:bg-muted/50"
                                >
                                    <ObjectIcon icon={object.icon} hue={null} />
                                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                        {object.label}
                                    </span>
                                    <span className="text-xs text-muted-foreground tabular-nums">
                                        {fieldCount(
                                            object.fields_count,
                                            'built-in field',
                                        )}
                                        {object.custom_fields_count > 0 &&
                                            ` · ${fieldCount(object.custom_fields_count)}`}
                                    </span>
                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>

                <div>
                    <h2 className="text-sm font-medium">Your objects</h2>
                    <p className="text-xs text-muted-foreground">
                        Kinds of record you define, each with its own list in
                        the sidebar.
                    </p>
                </div>

                <section className="space-y-2">
                    {live.length > 0 ? (
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {live.map((object, index) => (
                                <ObjectRow
                                    key={object.id}
                                    object={object}
                                    isFirst={index === 0}
                                    isLast={index === live.length - 1}
                                    onMove={(direction) =>
                                        move(object, direction)
                                    }
                                />
                            ))}
                        </ul>
                    ) : (
                        <p className="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                            No objects yet. Add one for anything your team keeps
                            track of, such as vehicles or licences.
                        </p>
                    )}

                    <Button variant="outline" onClick={() => setCreating(true)}>
                        <Plus />
                        New object
                    </Button>
                </section>

                {archived.length > 0 && (
                    <section className="space-y-2">
                        <div>
                            <h2 className="text-sm font-medium">Archived</h2>
                            <p className="text-xs text-muted-foreground">
                                Out of the sidebar. Their records are kept until
                                the object is deleted.
                            </p>
                        </div>
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {archived.map((object) => (
                                <ArchivedObjectRow
                                    key={object.id}
                                    object={object}
                                />
                            ))}
                        </ul>
                    </section>
                )}
            </div>

            {creating && <ObjectDialog onClose={() => setCreating(false)} />}
        </>
    );
}

function ObjectRow({
    object,
    isFirst,
    isLast,
    onMove,
}: {
    object: CustomObjectRow;
    isFirst: boolean;
    isLast: boolean;
    onMove: (direction: -1 | 1) => void;
}) {
    const [editing, setEditing] = useState(false);

    return (
        <li className="flex min-h-12 items-center gap-3 px-3 py-1.5">
            <ObjectIcon icon={object.icon} hue={object.hue} />
            <div className="min-w-0 flex-1">
                <Link
                    href={fields(object.id)}
                    className="block max-w-full truncate text-sm font-medium hover:underline"
                >
                    {object.name_plural}
                </Link>
                <p className="truncate text-xs text-muted-foreground">
                    {fieldCount(object.custom_fields_count)}
                    {object.description && ` · ${object.description}`}
                </p>
            </div>

            <span className="flex">
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${object.name_plural} up`}
                    disabled={isFirst}
                    onClick={() => onMove(-1)}
                >
                    <ArrowUp />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${object.name_plural} down`}
                    disabled={isLast}
                    onClick={() => onMove(1)}
                >
                    <ArrowDown />
                </Button>
            </span>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        aria-label={`More for ${object.name_plural}`}
                    >
                        <MoreHorizontal />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-40">
                    <DropdownMenuItem asChild>
                        <Link href={fields(object.id)}>
                            <ListPlus />
                            Fields
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => setEditing(true)}>
                        <Pencil />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onSelect={() =>
                            router.post(archive.url(object.id), {}, saveOptions)
                        }
                    >
                        <Archive />
                        Archive
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            {editing && (
                <ObjectDialog
                    object={object}
                    onClose={() => setEditing(false)}
                />
            )}
        </li>
    );
}

/**
 * An archived object: restore it, or delete it for good.
 */
function ArchivedObjectRow({ object }: { object: CustomObject }) {
    const [deleting, setDeleting] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <li className="flex h-12 items-center gap-3 px-3">
            <span className="opacity-60">
                <ObjectIcon icon={object.icon} hue={object.hue} />
            </span>
            <span className="min-w-0 flex-1 truncate text-sm text-muted-foreground">
                {object.name_plural}
            </span>
            <Button
                variant="ghost"
                size="sm"
                className="h-7"
                onClick={() =>
                    router.post(restore.url(object.id), {}, saveOptions)
                }
            >
                <ArchiveRestore />
                Restore
            </Button>
            <Button
                variant="ghost"
                size="icon"
                className="size-7"
                aria-label={`Delete ${object.name_plural}`}
                onClick={() => setDeleting(true)}
            >
                <Trash2 />
            </Button>

            <Dialog open={deleting} onOpenChange={setDeleting}>
                <DialogContent>
                    <DialogTitle>Delete {object.name_plural}?</DialogTitle>
                    <DialogDescription>
                        The object and everything in it will be gone for good,
                        and can&apos;t be restored.
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="ghost"
                            onClick={() => setDeleting(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={() =>
                                router.delete(destroy.url(object.id), {
                                    ...saveOptions,
                                    onStart: () => setProcessing(true),
                                    onFinish: () => setProcessing(false),
                                    onSuccess: () => setDeleting(false),
                                })
                            }
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </li>
    );
}

/**
 * Adds an object, or edits one: what one record and several are called, what
 * it is for, and how it looks in the sidebar.
 */
function ObjectDialog({
    object,
    onClose,
}: {
    object?: CustomObject;
    onClose: () => void;
}) {
    const [name, setName] = useState(object?.name ?? '');
    const [namePlural, setNamePlural] = useState(object?.name_plural ?? '');
    const [description, setDescription] = useState(object?.description ?? '');
    const [icon, setIcon] = useState(object?.icon ?? 'box');
    const [hue, setHue] = useState<number | null | undefined>(
        object ? object.hue : undefined,
    );

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogTitle>
                    {object ? 'Edit object' : 'New object'}
                </DialogTitle>
                <DialogDescription>
                    {object
                        ? 'Renaming an object keeps its records and fields.'
                        : 'An object gets its own list in the sidebar. You add its fields afterwards.'}
                </DialogDescription>
                <Form
                    {...(object ? update.form(object.id) : store.form())}
                    transform={() => ({
                        name: name.trim(),
                        name_plural: namePlural.trim(),
                        description: description.trim() || null,
                        icon,
                        ...(hue === undefined ? {} : { hue }),
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <FormField
                                    id="object-name"
                                    label="One is called"
                                    error={errors.name}
                                >
                                    <Input
                                        id="object-name"
                                        value={name}
                                        onChange={(event) =>
                                            setName(event.target.value)
                                        }
                                        maxLength={50}
                                        placeholder="Vehicle"
                                        required
                                        autoFocus
                                    />
                                </FormField>
                                <FormField
                                    id="object-name-plural"
                                    label="Several are called"
                                    error={errors.name_plural}
                                >
                                    <Input
                                        id="object-name-plural"
                                        value={namePlural}
                                        onChange={(event) =>
                                            setNamePlural(event.target.value)
                                        }
                                        maxLength={50}
                                        placeholder="Vehicles"
                                        required
                                    />
                                </FormField>
                            </div>

                            <FormField
                                id="object-description"
                                label="Description"
                                hint="Optional. Shown above the object's list."
                                error={errors.description}
                            >
                                <Input
                                    id="object-description"
                                    value={description}
                                    onChange={(event) =>
                                        setDescription(event.target.value)
                                    }
                                    maxLength={255}
                                />
                            </FormField>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid content-start gap-1.5">
                                    <span className="text-sm font-medium">
                                        Colour
                                    </span>
                                    <HuePicker value={hue} onChange={setHue} />
                                </div>
                                <div className="grid content-start gap-1.5">
                                    <span className="text-sm font-medium">
                                        Icon
                                    </span>
                                    <div className="flex flex-wrap gap-1">
                                        {Object.entries(objectIcons).map(
                                            ([key, Icon]) => (
                                                <button
                                                    key={key}
                                                    type="button"
                                                    aria-label={key}
                                                    aria-pressed={icon === key}
                                                    onClick={() => setIcon(key)}
                                                    className={cn(
                                                        'flex size-7 items-center justify-center rounded-md border text-muted-foreground',
                                                        icon === key &&
                                                            'border-primary bg-primary/8 text-foreground',
                                                    )}
                                                >
                                                    <Icon className="size-3.5" />
                                                </button>
                                            ),
                                        )}
                                    </div>
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={onClose}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {object ? 'Save' : 'Add object'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
