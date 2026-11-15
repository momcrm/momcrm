import { Head, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    MoreHorizontal,
    Plus,
    Star,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { HUE_CHOICES, hueLabel } from '@/components/hue-picker';
import NativeSelect from '@/components/native-select';
import SoftBadge from '@/components/soft-badge';
import { priorityIcons } from '@/components/tickets/ticket-meta';
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
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import {
    archive,
    destroy,
    reorder,
    restore,
    store,
    update,
} from '@/routes/choices';
import type { Choice, ChoiceList, Option } from '@/types';

type WorkflowChoice = Choice & { usage: number };

type WorkflowList = {
    value: ChoiceList;
    label: string;
    categories: Option[];
    icons: string[];
    /** Records may have none, as tickets may have no labels: no default, and deleting takes it off them. */
    is_optional: boolean;
    choices: WorkflowChoice[];
};

/**
 * What each category means, shown under its heading.
 */
const categoryHints: Record<string, string> = {
    open: 'Tickets still being worked on. New tickets start in the default.',
    closed: 'Finished tickets. Moving a ticket here closes it, and Close uses the default.',
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
 * The team's own ticket statuses and priorities: names, colours, order and
 * defaults. Statuses are grouped by whether they keep a ticket open.
 */
export default function Workflow({ lists }: { lists: WorkflowList[] }) {
    const [current, setCurrent] = useState<ChoiceList>('ticket_status');
    const list = lists.find((option) => option.value === current) ?? lists[0];

    return (
        <>
            <Head title="Workflow" />

            <h1 className="sr-only">Workflow</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Workflow"
                    description="The statuses, priorities and labels your team's tickets use. Changes apply to every ticket straight away."
                />

                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={current}
                    onValueChange={(value) =>
                        value && setCurrent(value as ChoiceList)
                    }
                >
                    {lists.map((option) => (
                        <ToggleGroupItem
                            key={option.value}
                            value={option.value}
                            className="px-3"
                        >
                            {option.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                <ListEditor key={list.value} list={list} />
            </div>
        </>
    );
}

function ListEditor({ list }: { list: WorkflowList }) {
    const live = list.choices.filter((choice) => !choice.is_archived);
    const archived = list.choices.filter((choice) => choice.is_archived);
    const groups =
        list.categories.length === 0
            ? [{ category: null, label: null, choices: live }]
            : [...list.categories]
                  .sort((a, b) =>
                      a.value === 'open' ? -1 : b.value === 'open' ? 1 : 0,
                  )
                  .map((category) => ({
                      category: category.value,
                      label: category.label,
                      choices: live.filter(
                          (choice) => choice.category === category.value,
                      ),
                  }));

    /**
     * Swaps a choice with its neighbour in the same group, keeping the rest of
     * the list where it is.
     */
    const move = (choice: WorkflowChoice, direction: -1 | 1) => {
        const ids = live.map((item) => item.id);
        const group = live.filter((item) => item.category === choice.category);
        const neighbour = group[group.indexOf(choice) + direction];

        if (!neighbour) {
            return;
        }

        const from = ids.indexOf(choice.id);
        const to = ids.indexOf(neighbour.id);
        [ids[from], ids[to]] = [ids[to], ids[from]];

        router.put(reorder.url(), { list: list.value, ids }, saveOptions);
    };

    return (
        <div className="space-y-6">
            {groups.map((group) => (
                <section key={group.category ?? 'all'} className="space-y-2">
                    {group.label && group.category && (
                        <div>
                            <h2 className="text-sm font-medium">
                                {group.label}
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                {categoryHints[group.category]}
                            </p>
                        </div>
                    )}
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {group.choices.map((choice, index) => (
                            <ChoiceRow
                                key={choice.id}
                                choice={choice}
                                list={list}
                                siblings={group.choices}
                                isFirst={index === 0}
                                isLast={index === group.choices.length - 1}
                                onMove={(direction) => move(choice, direction)}
                            />
                        ))}
                        <AddChoice list={list} category={group.category} />
                    </ul>
                </section>
            ))}

            {archived.length > 0 && (
                <section className="space-y-2">
                    <div>
                        <h2 className="text-sm font-medium">Archived</h2>
                        <p className="text-xs text-muted-foreground">
                            No longer offered. Tickets that use one keep it
                            until you change them.
                        </p>
                    </div>
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {archived.map((choice) => (
                            <ArchivedChoiceRow
                                key={choice.id}
                                choice={choice}
                                category={
                                    list.categories.find(
                                        (category) =>
                                            category.value === choice.category,
                                    )?.label
                                }
                                isOptional={list.is_optional}
                            />
                        ))}
                    </ul>
                </section>
            )}
        </div>
    );
}

function ChoiceRow({
    choice,
    list,
    siblings,
    isFirst,
    isLast,
    onMove,
}: {
    choice: WorkflowChoice;
    list: WorkflowList;
    siblings: WorkflowChoice[];
    isFirst: boolean;
    isLast: boolean;
    onMove: (direction: -1 | 1) => void;
}) {
    const [archiving, setArchiving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const save = (fields: Record<string, unknown>) =>
        router.patch(update.url(choice.id), fields as never, saveOptions);
    const Icon = choice.icon ? priorityIcons[choice.icon] : null;

    return (
        <li className="group flex h-11 items-center gap-2 px-3">
            <DropdownMenu>
                <DropdownMenuTrigger
                    aria-label={`Colour of ${choice.name}`}
                    className="flex size-6 shrink-0 items-center justify-center rounded-md hover:bg-accent"
                >
                    <span
                        className={cn('hue-dot', 'size-2.5 rounded-full')}
                        style={hueStyle(choice.hue)}
                    />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-auto p-1.5">
                    <div className="grid grid-cols-5 gap-1">
                        {HUE_CHOICES.map((hue) => (
                            <DropdownMenuItem
                                key={hue ?? 'grey'}
                                aria-label={hueLabel(hue)}
                                onSelect={() => save({ hue })}
                                className="justify-center p-1.5"
                            >
                                <span
                                    className={cn(
                                        'size-4 rounded-full hue-dot',
                                        hue === choice.hue &&
                                            'ring-2 ring-ring ring-offset-2 ring-offset-popover',
                                    )}
                                    style={hueStyle(hue)}
                                />
                            </DropdownMenuItem>
                        ))}
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            {list.icons.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        aria-label={`Icon of ${choice.name}`}
                        className="flex size-6 shrink-0 items-center justify-center rounded-md hover:bg-accent"
                    >
                        {Icon ? (
                            <Icon
                                className={cn('hue-text', 'size-3.5')}
                                style={hueStyle(choice.hue)}
                            />
                        ) : (
                            <span className="size-3.5 rounded-sm border border-dashed" />
                        )}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" className="w-auto p-1.5">
                        <div className="flex gap-1">
                            {list.icons.map((name) => {
                                const Option = priorityIcons[name];

                                return (
                                    <DropdownMenuItem
                                        key={name}
                                        aria-label={name}
                                        onSelect={() => save({ icon: name })}
                                        className={cn(
                                            'justify-center p-1.5',
                                            name === choice.icon && 'bg-accent',
                                        )}
                                    >
                                        {Option && (
                                            <Option className="size-4" />
                                        )}
                                    </DropdownMenuItem>
                                );
                            })}
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
            )}

            <input
                key={choice.name}
                defaultValue={choice.name}
                aria-label="Name"
                maxLength={50}
                onBlur={(event) => {
                    const name = event.target.value.trim();

                    if (name && name !== choice.name) {
                        save({ name });
                    } else {
                        event.target.value = choice.name;
                    }
                }}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.currentTarget.blur();
                    }

                    if (event.key === 'Escape') {
                        event.currentTarget.value = choice.name;
                        event.currentTarget.blur();
                    }
                }}
                className="h-8 min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-2 text-sm hover:border-border focus:border-ring focus:outline-none"
            />

            {choice.is_default && <SoftBadge tone="primary">Default</SoftBadge>}
            <span className="w-20 text-right text-xs text-subtle-foreground tabular-nums">
                {choice.usage === 1 ? '1 ticket' : `${choice.usage} tickets`}
            </span>

            <span className="flex">
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${choice.name} up`}
                    disabled={isFirst}
                    onClick={() => onMove(-1)}
                >
                    <ArrowUp />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Move ${choice.name} down`}
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
                        aria-label={`More for ${choice.name}`}
                    >
                        <MoreHorizontal />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                    {!list.is_optional && (
                        <>
                            <DropdownMenuItem
                                disabled={choice.is_default}
                                onSelect={() => save({ is_default: true })}
                            >
                                <Star />
                                Make default
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                        </>
                    )}
                    <DropdownMenuItem
                        disabled={
                            !list.is_optional &&
                            (choice.is_default || siblings.length <= 1)
                        }
                        onSelect={() => setArchiving(true)}
                    >
                        <Archive />
                        Archive
                    </DropdownMenuItem>
                    {list.is_optional && (
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => setDeleting(true)}
                        >
                            <Trash2 />
                            Delete
                        </DropdownMenuItem>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            <ArchiveChoiceDialog
                open={archiving}
                onOpenChange={setArchiving}
                choice={choice}
                replacements={
                    list.is_optional
                        ? []
                        : siblings.filter((item) => item.id !== choice.id)
                }
            />
            <DeleteChoiceDialog
                open={deleting}
                onOpenChange={setDeleting}
                choice={choice}
            />
        </li>
    );
}

/**
 * Archives a choice. Its tickets keep it, unless moved to another choice of
 * the same kind (offered only when there are replacements, so not for labels).
 */
function ArchiveChoiceDialog({
    open,
    onOpenChange,
    choice,
    replacements,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    choice: WorkflowChoice;
    replacements: WorkflowChoice[];
}) {
    const [replacementId, setReplacementId] = useState('');
    const [processing, setProcessing] = useState(false);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Archive {choice.name}?</DialogTitle>
                <DialogDescription>
                    {choice.usage === 0
                        ? "It won't be offered for tickets any more. You can restore it later."
                        : `It won't be offered for tickets any more. ${choice.usage === 1 ? 'The 1 ticket that uses it keeps' : `The ${choice.usage} tickets that use it keep`} it${replacements.length > 0 ? `, unless you move ${choice.usage === 1 ? 'it' : 'them'}` : ''}.`}
                </DialogDescription>
                {choice.usage > 0 && replacements.length > 0 && (
                    <NativeSelect
                        aria-label="Tickets that use it"
                        value={replacementId}
                        onChange={(event) =>
                            setReplacementId(event.target.value)
                        }
                    >
                        <option value="">Leave them in {choice.name}</option>
                        {replacements.map((item) => (
                            <option key={item.id} value={item.id}>
                                Move them to {item.name}
                            </option>
                        ))}
                    </NativeSelect>
                )}
                <DialogFooter className="gap-2">
                    <Button variant="ghost" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button
                        disabled={processing}
                        onClick={() =>
                            router.post(
                                archive.url(choice.id),
                                replacementId
                                    ? { replacement_id: replacementId }
                                    : {},
                                {
                                    ...saveOptions,
                                    onStart: () => setProcessing(true),
                                    onFinish: () => setProcessing(false),
                                    onSuccess: () => onOpenChange(false),
                                },
                            )
                        }
                    >
                        Archive
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/**
 * An archived choice: restore it, or delete it for good once nothing uses it.
 */
function ArchivedChoiceRow({
    choice,
    category,
    isOptional,
}: {
    choice: WorkflowChoice;
    category?: string;
    isOptional: boolean;
}) {
    const [deleting, setDeleting] = useState(false);
    const canDelete = isOptional || choice.usage === 0;

    return (
        <li className="flex h-11 items-center gap-2 px-3">
            <span className="flex size-6 shrink-0 items-center justify-center">
                <span
                    className={cn(
                        'hue-dot',
                        'size-2.5 rounded-full opacity-60',
                    )}
                    style={hueStyle(choice.hue)}
                />
            </span>
            <span className="min-w-0 flex-1 truncate px-2 text-sm text-muted-foreground">
                {choice.name}
            </span>
            {category && <SoftBadge tone="neutral">{category}</SoftBadge>}
            <span className="w-20 text-right text-xs text-subtle-foreground tabular-nums">
                {choice.usage === 1 ? '1 ticket' : `${choice.usage} tickets`}
            </span>
            <Button
                variant="ghost"
                size="sm"
                className="h-7"
                onClick={() =>
                    router.post(restore.url(choice.id), {}, saveOptions)
                }
            >
                <ArchiveRestore />
                Restore
            </Button>
            <Button
                variant="ghost"
                size="icon"
                className="size-7"
                aria-label={`Delete ${choice.name}`}
                title={
                    canDelete
                        ? undefined
                        : 'Tickets still use it, so it can only stay archived.'
                }
                disabled={!canDelete}
                onClick={() => setDeleting(true)}
            >
                <Trash2 />
            </Button>

            <DeleteChoiceDialog
                open={deleting}
                onOpenChange={setDeleting}
                choice={choice}
            />
        </li>
    );
}

/**
 * Deletes a choice for good. Only a label can go while tickets have it, and
 * it comes off them.
 */
function DeleteChoiceDialog({
    open,
    onOpenChange,
    choice,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    choice: WorkflowChoice;
}) {
    const [processing, setProcessing] = useState(false);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Delete {choice.name}?</DialogTitle>
                <DialogDescription>
                    {choice.usage === 0
                        ? 'No tickets use it.'
                        : `It comes off the ${choice.usage === 1 ? '1 ticket that has it' : `${choice.usage} tickets that have it`}.`}{' '}
                    It will be gone for good, and can&apos;t be restored.
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <Button variant="ghost" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={() =>
                            router.delete(destroy.url(choice.id), {
                                ...saveOptions,
                                onStart: () => setProcessing(true),
                                onFinish: () => setProcessing(false),
                                onSuccess: () => onOpenChange(false),
                            })
                        }
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function AddChoice({
    list,
    category,
}: {
    list: WorkflowList;
    category: string | null;
}) {
    const [name, setName] = useState('');
    const noun = {
        ticket_label: 'label',
        ticket_priority: 'priority',
        ticket_status: 'status',
    }[list.value];

    return (
        <li>
            <form
                className="flex h-11 items-center gap-2 px-3"
                onSubmit={(event) => {
                    event.preventDefault();

                    if (!name.trim()) {
                        return;
                    }

                    router.post(
                        store.url(),
                        {
                            list: list.value,
                            name: name.trim(),
                            icon: list.icons.length > 0 ? 'minus' : null,
                            category,
                        },
                        { ...saveOptions, onSuccess: () => setName('') },
                    );
                }}
            >
                <Plus className="mx-1 size-4 shrink-0 text-subtle-foreground" />
                <input
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder={`Add a ${noun} and press Enter`}
                    aria-label={`New ${noun}`}
                    maxLength={50}
                    className="h-8 flex-1 bg-transparent text-sm outline-none placeholder:text-subtle-foreground"
                />
            </form>
        </li>
    );
}
