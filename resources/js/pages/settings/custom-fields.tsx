import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import FormField from '@/components/form-field';
import Heading from '@/components/heading';
import { HUE_CHOICES, hueLabel } from '@/components/hue-picker';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import SoftBadge from '@/components/soft-badge';
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
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import {
    archive,
    destroy,
    edit,
    reorder,
    restore,
    store,
    update,
} from '@/routes/custom-fields';
import type {
    CustomField,
    CustomFieldRelation,
    CustomFieldTarget,
    CustomFieldType,
    Option,
} from '@/types';

type TypeOption = {
    value: CustomFieldType;
    label: string;
    has_options: boolean;
};

/** An option being edited; one not saved yet has no id. */
type DraftOption = { id: string | null; name: string; hue: number | null };

const relationNouns: Record<CustomFieldRelation, string> = {
    member: 'a team member',
    customer: 'a customer',
    contact: 'a contact',
};

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
 * The fields the team has added to its records, one kind of record at a
 * time. `?target=` keeps the open kind in the URL.
 */
export default function CustomFields({
    current,
    targets,
    types,
    fields,
}: {
    current: CustomFieldTarget;
    targets: Option[];
    types: TypeOption[];
    fields: CustomField[];
}) {
    const [creating, setCreating] = useState(false);
    const live = fields.filter((field) => !field.is_archived);
    const archived = fields.filter((field) => field.is_archived);
    const typeLabel = (type: CustomFieldType) =>
        types.find((option) => option.value === type)?.label ?? type;

    const move = (field: CustomField, direction: -1 | 1) => {
        const ids = live.map((item) => item.id);
        const from = ids.indexOf(field.id);
        const to = from + direction;

        if (to < 0 || to >= ids.length) {
            return;
        }

        [ids[from], ids[to]] = [ids[to], ids[from]];

        router.put(reorder.url(), { target: current, ids }, saveOptions);
    };

    return (
        <>
            <Head title="Custom fields" />

            <h1 className="sr-only">Custom fields</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Custom fields"
                    description="Extra fields your team keeps on its records."
                />

                <nav
                    aria-label="Record type"
                    className="flex flex-wrap gap-1 border-b"
                >
                    {targets.map((target) => (
                        <Link
                            key={target.value}
                            href={edit({ query: { target: target.value } })}
                            preserveScroll
                            aria-current={
                                target.value === current ? 'page' : undefined
                            }
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                target.value === current
                                    ? 'border-primary font-medium text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {target.label}
                        </Link>
                    ))}
                </nav>

                <section className="space-y-2">
                    {live.length > 0 ? (
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {live.map((field, index) => (
                                <FieldRow
                                    key={field.id}
                                    field={field}
                                    typeLabel={typeLabel(field.type)}
                                    types={types}
                                    isFirst={index === 0}
                                    isLast={index === live.length - 1}
                                    onMove={(direction) =>
                                        move(field, direction)
                                    }
                                />
                            ))}
                        </ul>
                    ) : (
                        <p className="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                            No custom fields here yet.
                        </p>
                    )}

                    <Button variant="outline" onClick={() => setCreating(true)}>
                        <Plus />
                        New field
                    </Button>
                </section>

                {archived.length > 0 && (
                    <section className="space-y-2">
                        <div>
                            <h2 className="text-sm font-medium">Archived</h2>
                            <p className="text-xs text-muted-foreground">
                                No longer shown on records. Their values are
                                kept until the field is deleted.
                            </p>
                        </div>
                        <ul className="divide-y rounded-lg border bg-card shadow-xs">
                            {archived.map((field) => (
                                <ArchivedFieldRow
                                    key={field.id}
                                    field={field}
                                    typeLabel={typeLabel(field.type)}
                                />
                            ))}
                        </ul>
                    </section>
                )}
            </div>

            {creating && (
                <FieldDialog
                    target={current}
                    types={types}
                    onClose={() => setCreating(false)}
                />
            )}
        </>
    );
}

function FieldRow({
    field,
    typeLabel,
    types,
    isFirst,
    isLast,
    onMove,
}: {
    field: CustomField;
    typeLabel: string;
    types: TypeOption[];
    isFirst: boolean;
    isLast: boolean;
    onMove: (direction: -1 | 1) => void;
}) {
    const [editing, setEditing] = useState(false);

    return (
        <li className="flex min-h-11 items-center gap-2 px-3 py-1.5">
            <div className="min-w-0 flex-1">
                <button
                    type="button"
                    onClick={() => setEditing(true)}
                    className="block max-w-full truncate text-left text-sm font-medium hover:underline"
                >
                    {field.name}
                </button>
                {(field.description ||
                    field.relation ||
                    field.options.length > 0) && (
                    <p className="truncate text-xs text-muted-foreground">
                        {field.options.length > 0
                            ? field.options
                                  .map((option) => option.name)
                                  .join(', ')
                            : field.relation
                              ? `Links to ${relationNouns[field.relation]}`
                              : field.description}
                    </p>
                )}
            </div>

            <SoftBadge tone="neutral">{typeLabel}</SoftBadge>

            <span className="flex">
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${field.name} up`}
                    disabled={isFirst}
                    onClick={() => onMove(-1)}
                >
                    <ArrowUp />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${field.name} down`}
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
                        aria-label={`More for ${field.name}`}
                    >
                        <MoreHorizontal />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-40">
                    <DropdownMenuItem onSelect={() => setEditing(true)}>
                        <Pencil />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onSelect={() =>
                            router.post(archive.url(field.id), {}, saveOptions)
                        }
                    >
                        <Archive />
                        Archive
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            {editing && (
                <FieldDialog
                    target={field.target}
                    types={types}
                    field={field}
                    onClose={() => setEditing(false)}
                />
            )}
        </li>
    );
}

/**
 * An archived field: restore it, or delete it for good.
 */
function ArchivedFieldRow({
    field,
    typeLabel,
}: {
    field: CustomField;
    typeLabel: string;
}) {
    const [deleting, setDeleting] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <li className="flex h-11 items-center gap-2 px-3">
            <span className="min-w-0 flex-1 truncate text-sm text-muted-foreground">
                {field.name}
            </span>
            <SoftBadge tone="neutral">{typeLabel}</SoftBadge>
            <Button
                variant="ghost"
                size="sm"
                className="h-7"
                onClick={() =>
                    router.post(restore.url(field.id), {}, saveOptions)
                }
            >
                <ArchiveRestore />
                Restore
            </Button>
            <Button
                variant="ghost"
                size="icon"
                className="size-7"
                aria-label={`Delete ${field.name}`}
                onClick={() => setDeleting(true)}
            >
                <Trash2 />
            </Button>

            <Dialog open={deleting} onOpenChange={setDeleting}>
                <DialogContent>
                    <DialogTitle>Delete {field.name}?</DialogTitle>
                    <DialogDescription>
                        The field and every value records hold for it will be
                        gone for good, and can&apos;t be restored.
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
                                router.delete(destroy.url(field.id), {
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
 * Adds a field, or edits one. A field's type is chosen once: changing it
 * later would change what the stored values mean.
 */
function FieldDialog({
    target,
    types,
    field,
    onClose,
}: {
    target: CustomFieldTarget;
    types: TypeOption[];
    field?: CustomField;
    onClose: () => void;
}) {
    const [name, setName] = useState(field?.name ?? '');
    const [type, setType] = useState<CustomFieldType>(field?.type ?? 'text');
    const [description, setDescription] = useState(field?.description ?? '');
    const relations = (usePage().props.relations ?? []) as Option[];
    const [relation, setRelation] = useState(relations[0]?.value ?? '');
    const [options, setOptions] = useState<DraftOption[]>(
        field?.options ?? [{ id: null, name: '', hue: null }],
    );
    const hasOptions =
        types.find((option) => option.value === type)?.has_options ?? false;

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogTitle>{field ? 'Edit field' : 'New field'}</DialogTitle>
                <DialogDescription>
                    {field
                        ? 'Renaming a field or an option keeps the values records already have.'
                        : 'The field appears on every record of this kind, empty until someone fills it in.'}
                </DialogDescription>
                <Form
                    {...(field ? update.form(field.id) : store.form())}
                    transform={() => ({
                        ...(field ? {} : { target, type }),
                        ...(!field && type === 'relation' ? { relation } : {}),
                        name,
                        description: description.trim() || null,
                        ...(hasOptions
                            ? {
                                  options: options.filter(
                                      (option) =>
                                          option.name.trim() !== '' ||
                                          option.id !== null,
                                  ),
                              }
                            : {}),
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="field-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="field-name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    maxLength={50}
                                    placeholder="Serial number"
                                    required
                                    autoFocus
                                />
                            </FormField>

                            {!field && (
                                <FormField
                                    id="field-type"
                                    label="Type"
                                    hint="This can't be changed later."
                                    error={errors.type}
                                >
                                    <NativeSelect
                                        id="field-type"
                                        value={type}
                                        onChange={(event) =>
                                            setType(
                                                event.target
                                                    .value as CustomFieldType,
                                            )
                                        }
                                    >
                                        {types.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}

                            {!field && type === 'relation' && (
                                <FormField
                                    id="field-relation"
                                    label="Links to"
                                    hint="This can't be changed later."
                                    error={errors.relation}
                                >
                                    <NativeSelect
                                        id="field-relation"
                                        value={relation}
                                        onChange={(event) =>
                                            setRelation(event.target.value)
                                        }
                                    >
                                        {relations.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}

                            {hasOptions && (
                                <OptionsEditor
                                    options={options}
                                    onChange={setOptions}
                                    errors={errors}
                                />
                            )}

                            <FormField
                                id="field-description"
                                label="Hint"
                                hint="Optional. Shown under the field, to say what belongs in it."
                                error={errors.description}
                            >
                                <Input
                                    id="field-description"
                                    value={description}
                                    onChange={(event) =>
                                        setDescription(event.target.value)
                                    }
                                    maxLength={255}
                                />
                            </FormField>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={onClose}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {field ? 'Save' : 'Add field'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * The options of a select, in the order they are offered.
 */
function OptionsEditor({
    options,
    onChange,
    errors,
}: {
    options: DraftOption[];
    onChange: (options: DraftOption[]) => void;
    errors: Record<string, string>;
}) {
    const change = (index: number, changes: Partial<DraftOption>) =>
        onChange(
            options.map((option, position) =>
                position === index ? { ...option, ...changes } : option,
            ),
        );

    const move = (index: number, direction: -1 | 1) => {
        const next = [...options];
        [next[index], next[index + direction]] = [
            next[index + direction],
            next[index],
        ];
        onChange(next);
    };

    return (
        <div className="grid gap-1.5">
            <span className="text-sm font-medium">Options</span>
            <ul className="divide-y rounded-md border">
                {options.map((option, index) => (
                    <li
                        key={option.id ?? `new-${index}`}
                        className="flex h-10 items-center gap-1 px-2"
                    >
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                type="button"
                                aria-label={`Colour of option ${index + 1}`}
                                className="flex size-6 shrink-0 items-center justify-center rounded-md hover:bg-accent"
                            >
                                <span
                                    className="size-2.5 rounded-full hue-dot"
                                    style={hueStyle(option.hue)}
                                />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="start"
                                className="w-auto p-1.5"
                            >
                                <div className="grid grid-cols-5 gap-1">
                                    {HUE_CHOICES.map((hue) => (
                                        <DropdownMenuItem
                                            key={hue ?? 'grey'}
                                            aria-label={hueLabel(hue)}
                                            onSelect={() =>
                                                change(index, { hue })
                                            }
                                            className="justify-center p-1.5"
                                        >
                                            <span
                                                className={cn(
                                                    'size-4 rounded-full hue-dot',
                                                    hue === option.hue &&
                                                        'ring-2 ring-ring ring-offset-2 ring-offset-popover',
                                                )}
                                                style={hueStyle(hue)}
                                            />
                                        </DropdownMenuItem>
                                    ))}
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <input
                            value={option.name}
                            onChange={(event) =>
                                change(index, { name: event.target.value })
                            }
                            aria-label={`Option ${index + 1}`}
                            placeholder="Option name"
                            maxLength={50}
                            aria-invalid={
                                errors[`options.${index}.name`]
                                    ? true
                                    : undefined
                            }
                            className="h-8 min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-2 text-sm hover:border-border focus:border-ring focus:outline-none aria-invalid:border-destructive"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={`Move option ${index + 1} up`}
                            disabled={index === 0}
                            onClick={() => move(index, -1)}
                        >
                            <ArrowUp />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={`Move option ${index + 1} down`}
                            disabled={index === options.length - 1}
                            onClick={() => move(index, 1)}
                        >
                            <ArrowDown />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7"
                            aria-label={`Remove option ${index + 1}`}
                            disabled={options.length === 1}
                            onClick={() =>
                                onChange(
                                    options.filter(
                                        (_, position) => position !== index,
                                    ),
                                )
                            }
                        >
                            <X />
                        </Button>
                    </li>
                ))}
            </ul>
            <div>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                        onChange([
                            ...options,
                            { id: null, name: '', hue: null },
                        ])
                    }
                >
                    <Plus />
                    Add option
                </Button>
            </div>
            <InputError
                message={
                    errors.options ??
                    Object.entries(errors).find(([key]) =>
                        key.startsWith('options.'),
                    )?.[1]
                }
            />
        </div>
    );
}
