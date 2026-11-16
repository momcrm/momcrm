import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Archive,
    Check,
    ChevronRight,
    Link2,
    Maximize2,
    Plus,
    RotateCcw,
    Star,
} from 'lucide-react';
import { Fragment, useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import {
    AssigneeAvatar,
    assigneeChoices,
    ChoiceTag,
    daysUntil,
    defaultStatusId,
    isClosedStatus,
    useIsClosedStatus,
    PriorityIcon,
    ProjectTag,
    TicketStatusPill,
    usePriority,
    useTicketPriorities,
    useTicketTypes,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import { LabelChips, useLabels } from '@/components/labels';
import Attachments from '@/components/attachments';
import TicketConversation from '@/components/tickets/ticket-conversation';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { show } from '@/routes/tickets';
import { store as storeAttachment } from '@/routes/tickets/attachments';
import type {
    Choice,
    TicketDrawerData,
    LabelTag,
    TicketPickerOptions,
} from '@/types';

const NONE = '__none__';

/**
 * A ticket opened beside the list it was picked from: every field edits in
 * place, and the URL (?ticket=) keeps it open on reload or when shared.
 */
export default function TicketDrawer({
    ticket,
    options,
}: {
    ticket: TicketDrawerData | null;
    options: TicketPickerOptions;
}) {
    const { closeTicket } = useTicketActions();

    return (
        <Sheet
            open={ticket !== null}
            onOpenChange={(open) => !open && closeTicket()}
        >
            <SheetContent className="w-full gap-0 overflow-y-auto p-0 sm:max-w-[34rem] [&>button:last-child]:top-2.75">
                {ticket && (
                    <DrawerBody
                        key={ticket.id}
                        ticket={ticket}
                        options={options}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}

function DrawerBody({
    ticket,
    options,
}: {
    ticket: TicketDrawerData;
    options: TicketPickerOptions;
}) {
    const { updateTicket, toggleStar, archiveTicket, openTicket, closeTicket } =
        useTicketActions();
    const save = (fields: Record<string, unknown>) =>
        updateTicket(ticket.id, fields);
    const statuses = useTicketStatuses(ticket.status_id);
    const isClosed = isClosedStatus(statuses, ticket.status_id);
    const priorities = useTicketPriorities(ticket.priority_id);
    const priority = usePriority(ticket.priority_id);
    const labels = useLabels(...ticket.labels.map((label) => label.id));
    const types = useTicketTypes(ticket.type_id);
    const overdueDays =
        ticket.due_on && !isClosed ? -daysUntil(ticket.due_on) : 0;

    const copyLink = () => {
        void navigator.clipboard
            .writeText(`${window.location.origin}${show.url(ticket.id)}`)
            .then(() => toast.success('Link copied.'));
    };

    return (
        <>
            <div className="flex h-12 shrink-0 items-center gap-1 border-b px-4 pr-10.5 text-sm">
                <span className="flex min-w-0 items-center gap-1 text-muted-foreground">
                    {ticket.parent && (
                        <>
                            <button
                                type="button"
                                onClick={() => openTicket(ticket.parent!.id)}
                                className="truncate hover:text-foreground"
                            >
                                #{ticket.parent.number} {ticket.parent.title}
                            </button>
                            <ChevronRight className="size-3.5 shrink-0" />
                        </>
                    )}
                    <span className="font-mono text-xs">#{ticket.number}</span>
                </span>
                <div className="ml-auto flex items-center gap-0.5">
                    {(isClosed ? ticket.can.reopen : ticket.can.close) && (
                        <Button
                            variant={isClosed ? 'outline' : 'ghost'}
                            size="sm"
                            className="h-7 gap-1.5"
                            onClick={() =>
                                save({
                                    status_id: defaultStatusId(
                                        statuses,
                                        isClosed ? 'open' : 'closed',
                                    ),
                                })
                            }
                        >
                            {isClosed ? <RotateCcw /> : <Check />}
                            {isClosed ? 'Reopen' : 'Mark complete'}
                        </Button>
                    )}
                    <IconAction
                        label={ticket.is_favorite ? 'Unstar' : 'Star'}
                        onClick={() => toggleStar(ticket.id)}
                    >
                        <Star
                            className={cn(
                                ticket.is_favorite &&
                                    'fill-amber-400 text-amber-400',
                            )}
                        />
                    </IconAction>
                    <IconAction label="Copy link" onClick={copyLink}>
                        <Link2 />
                    </IconAction>
                    {ticket.can.archive && (
                        <IconAction
                            label="Archive"
                            onClick={() => {
                                archiveTicket(ticket.id);
                                closeTicket();
                            }}
                        >
                            <Archive />
                        </IconAction>
                    )}
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-7"
                                asChild
                            >
                                <Link
                                    href={show(ticket.id)}
                                    aria-label="Open full page"
                                >
                                    <Maximize2 />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Open full page</TooltipContent>
                    </Tooltip>
                </div>
            </div>

            {overdueDays > 0 && (
                <div className="flex items-center gap-2 border-b bg-destructive/8 px-5 py-2 text-sm text-destructive">
                    <AlertTriangle className="size-4 shrink-0" />
                    <span className="flex-1">
                        Overdue by {overdueDays}{' '}
                        {overdueDays === 1 ? 'day' : 'days'}
                    </span>
                    <label className="flex items-center gap-1.5 text-xs font-medium">
                        Reschedule
                        <input
                            type="date"
                            aria-label="New due date"
                            className="rounded border border-destructive/30 bg-card px-1.5 py-0.5 text-foreground"
                            onChange={(event) =>
                                event.target.value &&
                                save({ due_on: event.target.value })
                            }
                        />
                    </label>
                </div>
            )}

            <div className="space-y-6 px-5 py-5">
                <div>
                    <SheetTitle asChild>
                        <InlineTitle
                            value={ticket.title}
                            onSave={(title) => save({ title })}
                        />
                    </SheetTitle>
                    <SheetDescription className="sr-only">
                        Ticket #{ticket.number} details
                    </SheetDescription>
                </div>

                <dl className="grid grid-cols-[7.5rem_1fr] items-center gap-x-3 gap-y-1 text-sm">
                    <Property label="Status">
                        <Picker
                            value={ticket.status_id ?? NONE}
                            choices={statuses.map((status) => ({
                                value: status.id,
                                label: status.name,
                                hue: status.hue,
                            }))}
                            onChange={(statusId) =>
                                save({ status_id: statusId })
                            }
                        >
                            <TicketStatusPill statusId={ticket.status_id} />
                        </Picker>
                    </Property>
                    <Property label="Priority">
                        <Picker
                            value={ticket.priority_id ?? NONE}
                            choices={priorities.map((priority) => ({
                                value: priority.id,
                                label: priority.name,
                            }))}
                            onChange={(priorityId) =>
                                save({ priority_id: priorityId })
                            }
                        >
                            <PriorityIcon priorityId={ticket.priority_id} />
                            {priority?.name ?? 'None'}
                        </Picker>
                    </Property>
                    <Property label="Type">
                        <OptionalChoicePicker
                            value={ticket.type_id}
                            choices={types}
                            none="No type"
                            onChange={(type_id) => save({ type_id })}
                        />
                    </Property>
                    <Property label="Group">
                        <OptionalChoicePicker
                            value={ticket.group_id}
                            choices={options.groups}
                            none="No group"
                            onChange={(group_id) => save({ group_id })}
                        />
                    </Property>
                    <Property label="Assignee">
                        <Picker
                            value={ticket.assignee_id ?? NONE}
                            choices={[
                                { value: NONE, label: 'Unassigned' },
                                ...assigneeChoices(
                                    options.members,
                                    options.groups.find(
                                        (group) => group.id === ticket.group_id,
                                    ),
                                ),
                            ]}
                            onChange={(id) =>
                                save({ assignee_id: id === NONE ? null : id })
                            }
                        >
                            <AssigneeAvatar name={ticket.assignee_name} />
                            {ticket.assignee_name ?? (
                                <span className="text-muted-foreground">
                                    Unassigned
                                </span>
                            )}
                        </Picker>
                    </Property>
                    <Property label="Due date">
                        <DateField
                            value={ticket.due_on}
                            label="Due date"
                            onChange={(due_on) => save({ due_on })}
                        />
                    </Property>
                    <Property label="Start date">
                        <DateField
                            value={ticket.starts_on}
                            label="Start date"
                            onChange={(starts_on) => save({ starts_on })}
                        />
                    </Property>
                    <Property label="Project">
                        <Picker
                            value={ticket.project_id ?? NONE}
                            choices={[
                                { value: NONE, label: 'No project' },
                                ...options.projects.map((project) => ({
                                    value: project.id,
                                    label: project.name,
                                    hue: project.hue,
                                })),
                            ]}
                            onChange={(id) =>
                                save({ project_id: id === NONE ? null : id })
                            }
                        >
                            {ticket.project_name ? (
                                <ProjectTag
                                    name={ticket.project_name}
                                    hue={ticket.project_hue}
                                />
                            ) : (
                                <span className="text-muted-foreground">
                                    No project
                                </span>
                            )}
                        </Picker>
                    </Property>
                    <Property label="Customer">
                        <Picker
                            value={ticket.customer_id ?? NONE}
                            choices={[
                                { value: NONE, label: 'No customer' },
                                ...options.customers.map((customer) => ({
                                    value: customer.id,
                                    label: customer.name,
                                })),
                            ]}
                            onChange={(id) =>
                                save({ customer_id: id === NONE ? null : id })
                            }
                        >
                            {ticket.customer_name ?? (
                                <span className="text-muted-foreground">
                                    No customer
                                </span>
                            )}
                        </Picker>
                    </Property>
                    <Property label="Labels">
                        <LabelPicker
                            selected={ticket.labels.map((label) => label.id)}
                            options={labels}
                            onChange={(label_ids) => save({ label_ids })}
                        >
                            {ticket.labels.length > 0 ? (
                                <LabelChips labels={ticket.labels} />
                            ) : (
                                <span className="text-muted-foreground">
                                    Add labels
                                </span>
                            )}
                        </LabelPicker>
                    </Property>
                    <Property label="Estimate">
                        <EstimateField
                            minutes={ticket.estimate_minutes}
                            onChange={(estimate_minutes) =>
                                save({ estimate_minutes })
                            }
                        />
                    </Property>
                </dl>

                <section className="space-y-2">
                    <h3 className="text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                        Description
                    </h3>
                    <Textarea
                        defaultValue={ticket.description ?? ''}
                        placeholder="Add more detail…"
                        rows={4}
                        className="resize-y border-transparent bg-transparent px-2 shadow-none hover:border-border focus-visible:border-input"
                        onBlur={(event) => {
                            const description = event.target.value.trim();

                            if (description !== (ticket.description ?? '')) {
                                save({ description: description || null });
                            }
                        }}
                    />
                </section>

                {!ticket.parent && <Subtasks ticket={ticket} />}

                <section className="space-y-2">
                    <h3 className="text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                        Files
                    </h3>
                    <Attachments
                        attachments={ticket.attachments}
                        uploadUrl={storeAttachment.url(ticket.id)}
                        reload={['drawer']}
                        can={ticket.attachment_can}
                    />
                </section>

                <TicketConversation
                    ticketId={ticket.id}
                    comments={ticket.comments}
                    activity={ticket.activity}
                    members={options.members}
                    reload={['drawer']}
                />
            </div>
        </>
    );
}

function IconAction({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={label}
                    onClick={onClick}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function InlineTitle({
    value,
    onSave,
    ...props
}: {
    value: string;
    onSave: (value: string) => void;
}) {
    return (
        <h2 {...props}>
            <input
                defaultValue={value}
                aria-label="Title"
                className="-ml-2 w-full rounded-md bg-transparent px-2 py-1 text-lg font-semibold outline-none hover:bg-accent/60 focus:bg-accent/60"
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.currentTarget.blur();
                    }
                }}
                onBlur={(event) => {
                    const title = event.target.value.trim();

                    if (title && title !== value) {
                        onSave(title);
                    } else {
                        event.target.value = value;
                    }
                }}
            />
        </h2>
    );
}

function Property({ label, children }: { label: string; children: ReactNode }) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="min-w-0">{children}</dd>
        </>
    );
}

const triggerClass =
    'flex h-8 w-full min-w-0 items-center gap-2 rounded-md px-2 text-left hover:bg-accent/60 data-[state=open]:bg-accent/60';

function Picker({
    value,
    choices,
    onChange,
    children,
}: {
    value: string;
    /** A choice's section, when given, heads it and the choices after it. */
    choices: {
        value: string;
        label: string;
        hue?: number | null;
        section?: string;
    }[];
    onChange: (value: string) => void;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger className={triggerClass}>
                {children}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="max-h-72 w-56 overflow-y-auto"
            >
                <DropdownMenuRadioGroup
                    value={value}
                    onValueChange={(next) => next !== value && onChange(next)}
                >
                    {choices.map((choice, index) => (
                        <Fragment key={choice.value}>
                            {choice.section &&
                                choice.section !==
                                    choices[index - 1]?.section && (
                                    <DropdownMenuLabel className="text-xs font-normal text-muted-foreground">
                                        {choice.section}
                                    </DropdownMenuLabel>
                                )}
                            <DropdownMenuRadioItem value={choice.value}>
                                {choice.hue !== undefined && (
                                    <span
                                        aria-hidden
                                        className="size-2 rounded-[3px] hue-dot"
                                        style={hueStyle(choice.hue)}
                                    />
                                )}
                                {choice.label}
                            </DropdownMenuRadioItem>
                        </Fragment>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * A pick from one of the team's optional lists, such as the ticket's type or
 * group, which can also be cleared.
 */
function OptionalChoicePicker({
    value,
    choices,
    none,
    onChange,
}: {
    value: string | null;
    choices: Pick<Choice, 'id' | 'name' | 'hue'>[];
    none: string;
    onChange: (value: string | null) => void;
}) {
    const current = choices.find((choice) => choice.id === value);

    return (
        <Picker
            value={value ?? NONE}
            choices={[
                { value: NONE, label: none },
                ...choices.map((choice) => ({
                    value: choice.id,
                    label: choice.name,
                    hue: choice.hue,
                })),
            ]}
            onChange={(id) => onChange(id === NONE ? null : id)}
        >
            {current ? (
                <ChoiceTag choice={current} />
            ) : (
                <span className="text-muted-foreground">{none}</span>
            )}
        </Picker>
    );
}

function LabelPicker({
    selected,
    options,
    onChange,
    children,
}: {
    selected: string[];
    options: LabelTag[];
    onChange: (ids: string[]) => void;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(triggerClass, 'h-auto min-h-8 py-1')}
            >
                {children}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
                {options.length === 0 && (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">
                        No labels yet.
                    </p>
                )}
                {options.map((label) => (
                    <DropdownMenuCheckboxItem
                        key={label.id}
                        checked={selected.includes(label.id)}
                        onSelect={(event) => event.preventDefault()}
                        onCheckedChange={(checked) =>
                            onChange(
                                checked
                                    ? [...selected, label.id]
                                    : selected.filter((id) => id !== label.id),
                            )
                        }
                    >
                        <span
                            aria-hidden
                            className={cn('hue-dot', 'size-2 rounded-full')}
                            style={hueStyle(label.hue)}
                        />
                        {label.name}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function DateField({
    value,
    label,
    onChange,
}: {
    value: string | null;
    label: string;
    onChange: (value: string | null) => void;
}) {
    return (
        <input
            type="date"
            aria-label={label}
            defaultValue={value ?? ''}
            className={cn(triggerClass, !value && 'text-muted-foreground')}
            onChange={(event) => onChange(event.target.value || null)}
        />
    );
}

function EstimateField({
    minutes,
    onChange,
}: {
    minutes: number | null;
    onChange: (minutes: number | null) => void;
}) {
    return (
        <div className="flex items-center gap-1.5">
            <Input
                type="number"
                min={0}
                step={0.25}
                aria-label="Estimate in hours"
                placeholder="—"
                defaultValue={minutes === null ? '' : minutes / 60}
                className="h-8 w-20 border-transparent bg-transparent px-2 shadow-none hover:border-border"
                onBlur={(event) => {
                    const hours = event.target.value;
                    const next =
                        hours === '' ? null : Math.round(Number(hours) * 60);

                    if (next !== minutes) {
                        onChange(next);
                    }
                }}
            />
            <span className="text-muted-foreground">hours</span>
        </div>
    );
}

function Subtasks({ ticket }: { ticket: TicketDrawerData }) {
    const { addSubtask, openTicket, updateTicket } = useTicketActions();
    const statuses = useTicketStatuses();
    const isClosed = useIsClosedStatus();
    const [title, setTitle] = useState('');
    const done = ticket.subtasks.filter((subtask) =>
        isClosed(subtask.status_id),
    ).length;

    return (
        <section className="space-y-2">
            <h3 className="flex items-center gap-2 text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                Subtasks
                {ticket.subtasks.length > 0 && (
                    <span className="font-mono normal-case">
                        {done}/{ticket.subtasks.length}
                    </span>
                )}
            </h3>
            <ul className="space-y-0.5">
                {ticket.subtasks.map((subtask) => {
                    const isDone = isClosed(subtask.status_id);

                    return (
                        <li
                            key={subtask.id}
                            className="group flex h-8 items-center gap-2 rounded-md px-2 hover:bg-accent/60"
                        >
                            <button
                                type="button"
                                aria-label={
                                    isDone
                                        ? `Reopen ${subtask.title}`
                                        : `Complete ${subtask.title}`
                                }
                                onClick={() =>
                                    updateTicket(subtask.id, {
                                        status_id: defaultStatusId(
                                            statuses,
                                            isDone ? 'open' : 'closed',
                                        ),
                                    })
                                }
                                className={cn(
                                    'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                    isDone
                                        ? 'border-success bg-success text-white'
                                        : 'border-border-strong',
                                )}
                            >
                                {isDone && <Check className="size-3" />}
                            </button>
                            <button
                                type="button"
                                onClick={() => openTicket(subtask.id)}
                                className={cn(
                                    'min-w-0 flex-1 truncate text-left',
                                    isDone &&
                                        'text-muted-foreground line-through',
                                )}
                            >
                                {subtask.title}
                            </button>
                            <span className="font-mono text-xs text-subtle-foreground">
                                #{subtask.number}
                            </span>
                        </li>
                    );
                })}
            </ul>
            <form
                className="flex items-center gap-2 px-2"
                onSubmit={(event) => {
                    event.preventDefault();

                    if (title.trim()) {
                        addSubtask(ticket.id, title.trim());
                        setTitle('');
                    }
                }}
            >
                <Plus className="size-4 shrink-0 text-subtle-foreground" />
                <input
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    placeholder="Add a subtask"
                    aria-label="New subtask"
                    className="h-8 flex-1 bg-transparent text-sm outline-none placeholder:text-subtle-foreground"
                />
            </form>
        </section>
    );
}
