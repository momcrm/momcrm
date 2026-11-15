import { Head, router } from '@inertiajs/react';
import {
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
import { colorDot, colorText } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { destroy, reorder, store, update } from '@/routes/choices';
import type { Choice, ChoiceList, ColorName, Option } from '@/types';

type WorkflowChoice = Choice & { usage: number };

type WorkflowList = {
    value: ChoiceList;
    label: string;
    categories: Option[];
    icons: string[];
    choices: WorkflowChoice[];
};

const colors = Object.keys(colorDot) as ColorName[];

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
                    description="The statuses and priorities your team's tickets use. Changes apply to every ticket straight away."
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
                    {[...lists]
                        .sort(
                            (a, b) =>
                                Number(b.value === 'ticket_status') -
                                Number(a.value === 'ticket_status'),
                        )
                        .map((option) => (
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
    const groups =
        list.categories.length === 0
            ? [{ category: null, label: null, choices: list.choices }]
            : [...list.categories]
                  .sort((a, b) =>
                      a.value === 'open' ? -1 : b.value === 'open' ? 1 : 0,
                  )
                  .map((category) => ({
                      category: category.value,
                      label: category.label,
                      choices: list.choices.filter(
                          (choice) => choice.category === category.value,
                      ),
                  }));

    /**
     * Swaps a choice with its neighbour in the same group, keeping the rest of
     * the list where it is.
     */
    const move = (choice: WorkflowChoice, direction: -1 | 1) => {
        const ids = list.choices.map((item) => item.id);
        const group = list.choices.filter(
            (item) => item.category === choice.category,
        );
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
                        className={cn(
                            'size-2.5 rounded-full',
                            colorDot[choice.color],
                        )}
                    />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-auto p-1.5">
                    <div className="grid grid-cols-4 gap-1">
                        {colors.map((color) => (
                            <DropdownMenuItem
                                key={color}
                                aria-label={color}
                                onSelect={() => save({ color })}
                                className="justify-center p-1.5"
                            >
                                <span
                                    className={cn(
                                        'size-4 rounded-full',
                                        colorDot[color],
                                        color === choice.color &&
                                            'ring-2 ring-ring ring-offset-2 ring-offset-popover',
                                    )}
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
                                className={cn(
                                    'size-3.5',
                                    colorText[choice.color],
                                )}
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
                    <DropdownMenuItem
                        disabled={choice.is_default}
                        onSelect={() => save({ is_default: true })}
                    >
                        <Star />
                        Make default
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        variant="destructive"
                        disabled={choice.is_default || siblings.length <= 1}
                        onSelect={() => setDeleting(true)}
                    >
                        <Trash2 />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <DeleteChoiceDialog
                open={deleting}
                onOpenChange={setDeleting}
                choice={choice}
                replacements={siblings.filter((item) => item.id !== choice.id)}
            />
        </li>
    );
}

function DeleteChoiceDialog({
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
    const [replacementId, setReplacementId] = useState(
        replacements.find((item) => item.is_default)?.id ??
            replacements[0]?.id ??
            '',
    );
    const [processing, setProcessing] = useState(false);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Delete {choice.name}?</DialogTitle>
                <DialogDescription>
                    {choice.usage === 0
                        ? 'No tickets use it, so nothing else changes.'
                        : `${choice.usage === 1 ? '1 ticket uses' : `${choice.usage} tickets use`} it. Choose where to move ${choice.usage === 1 ? 'it' : 'them'}.`}
                </DialogDescription>
                {choice.usage > 0 && (
                    <NativeSelect
                        aria-label="Move tickets to"
                        value={replacementId}
                        onChange={(event) =>
                            setReplacementId(event.target.value)
                        }
                    >
                        {replacements.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </NativeSelect>
                )}
                <DialogFooter className="gap-2">
                    <Button variant="ghost" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={() =>
                            router.delete(destroy.url(choice.id), {
                                data:
                                    choice.usage > 0
                                        ? { replacement_id: replacementId }
                                        : {},
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
    const noun = list.value === 'ticket_status' ? 'status' : 'priority';

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
                            color: 'slate',
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
