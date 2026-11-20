import { Link, router } from '@inertiajs/react';
import LinkedArticles from '@/components/articles/linked-articles';
import LinkedRecords from '@/components/custom-fields/linked-records';
import {
    AlertTriangle,
    Archive,
    Check,
    ChevronRight,
    Link2,
    Maximize2,
    Minimize2,
    Plus,
    RotateCcw,
    Star,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import {
    AssigneeAvatar,
    assigneeChoices,
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
import {
    InlineCustomFieldInput,
    useCustomFields,
} from '@/components/custom-fields/custom-field-input';
import { LabelChips, useLabels } from '@/components/labels';
import Attachments from '@/components/attachments';
import {
    DateField,
    LabelPicker,
    OptionalChoicePicker,
    Picker,
} from '@/components/tickets/property-editors';
import TicketConversation from '@/components/tickets/ticket-conversation';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
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
import { show as showCustomer } from '@/routes/customers';
import { show as showProject } from '@/routes/projects';
import { index, show } from '@/routes/tickets';
import { store as storeComment } from '@/routes/comments';
import { store as storeAttachment } from '@/routes/tickets/attachments';
import type { TicketDrawerData, TicketPickerOptions } from '@/types';

const NONE = '__none__';

/** Remembers whether the drawer opens as a side panel or a full page. */
const FULL_PAGE_KEY = 'ticket-drawer-full-page';

function readFullPage(): boolean {
    try {
        return window.localStorage.getItem(FULL_PAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * Where a ticket's details are shown: beside a list, expanded over it, or
 * as its own page at /tickets/{id}.
 */
type TicketLayout = 'side' | 'full' | 'page';

/**
 * A ticket opened beside the list it was picked from: every field edits in
 * place, and the URL (?ticket=) keeps it open on reload or when shared. It
 * can expand into a centred full-page panel, and remembers that choice.
 */
export default function TicketDrawer({
    ticket,
    options,
}: {
    ticket: TicketDrawerData | null;
    options: TicketPickerOptions;
}) {
    const { closeTicket } = useTicketActions();
    const [isFullPage, setIsFullPage] = useState(readFullPage);

    const toggleFullPage = () => {
        const next = !isFullPage;

        setIsFullPage(next);

        try {
            window.localStorage.setItem(FULL_PAGE_KEY, next ? '1' : '0');
        } catch {
            // Private windows can refuse storage; the choice lasts until reload.
        }
    };

    return (
        <Sheet
            open={ticket !== null}
            onOpenChange={(open) => !open && closeTicket()}
        >
            <SheetContent
                className={cn(
                    'w-full gap-0 overflow-y-auto p-0 sm:max-w-[34rem] [&>button:last-child]:top-2.75',
                    isFullPage &&
                        'data-[state=closed]:slide-out-to-right-0 data-[state=closed]:zoom-out-[0.98] data-[state=open]:slide-in-from-right-0 data-[state=open]:zoom-in-[0.98] sm:inset-y-6 sm:right-[max(1.5rem,calc(50%-490px))] sm:left-[max(1.5rem,calc(50%-490px))] sm:h-auto sm:w-auto sm:max-w-none sm:rounded-xl sm:border',
                )}
            >
                {ticket && (
                    <TicketDetails
                        key={ticket.id}
                        ticket={ticket}
                        options={options}
                        layout={isFullPage ? 'full' : 'side'}
                        onToggleFullPage={toggleFullPage}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}

/**
 * A ticket's header, properties, description, subtasks, files and
 * conversation, all editable in place. The drawer shows it beside a list or
 * as a full-page panel; the ticket's own page shows it with no drawer around
 * it, so opening another ticket goes to that ticket's page.
 */
export function TicketDetails({
    ticket,
    options,
    layout,
    onToggleFullPage,
}: {
    ticket: TicketDrawerData;
    options: TicketPickerOptions;
    layout: TicketLayout;
    onToggleFullPage?: () => void;
}) {
    const actions = useTicketActions();
    const { updateTicket, toggleStar, archiveTicket } = actions;
    const isPage = layout === 'page';
    const openTicket = isPage
        ? (id: string) => router.visit(show(id))
        : actions.openTicket;
    const closeTicket = isPage
        ? () => router.visit(index())
        : actions.closeTicket;
    const save = (fields: Record<string, unknown>) =>
        updateTicket(ticket.id, fields);
    const statuses = useTicketStatuses(ticket.status_id);
    const isClosed = isClosedStatus(statuses, ticket.status_id);
    const priorities = useTicketPriorities(ticket.priority_id);
    const priority = usePriority(ticket.priority_id);
    const labels = useLabels(...ticket.labels.map((label) => label.id));
    const customFields = useCustomFields('ticket');
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
            <div
                className={cn(
                    'flex h-12 shrink-0 items-center gap-1 border-b px-4 text-sm',
                    !isPage && 'pr-10.5',
                )}
            >
                <span className="flex min-w-0 items-center gap-1 text-muted-foreground">
                    {ticket.project_id && ticket.project_name ? (
                        <Link
                            href={showProject(ticket.project_id)}
                            className="flex h-6.5 min-w-0 items-center gap-1.5 rounded-md px-1.5 text-ui text-foreground hover:bg-accent"
                        >
                            <span
                                aria-hidden
                                className="size-2 shrink-0 rounded-[3px] hue-dot"
                                style={hueStyle(ticket.project_hue)}
                            />
                            <span className="truncate">
                                {ticket.project_name}
                            </span>
                        </Link>
                    ) : ticket.customer_id && ticket.customer_name ? (
                        <Link
                            href={showCustomer(ticket.customer_id)}
                            className="flex h-6.5 min-w-0 items-center rounded-md px-1.5 text-ui text-foreground hover:bg-accent"
                        >
                            <span className="truncate">
                                {ticket.customer_name}
                            </span>
                        </Link>
                    ) : null}
                    {(ticket.project_name || ticket.customer_name) && (
                        <span aria-hidden className="text-subtle-foreground">
                            /
                        </span>
                    )}
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
                    <span className="shrink-0 px-1.5 font-mono text-[11.5px] text-subtle-foreground">
                        #{ticket.number}
                    </span>
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
                    {onToggleFullPage && (
                        <IconAction
                            label={
                                layout === 'full' ? 'Side panel' : 'Full page'
                            }
                            onClick={onToggleFullPage}
                            className="hidden sm:inline-flex"
                        >
                            {layout === 'full' ? <Minimize2 /> : <Maximize2 />}
                        </IconAction>
                    )}
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

            <div
                className={cn(
                    'space-y-6',
                    layout === 'side'
                        ? 'px-5 py-5'
                        : 'px-5 py-5 sm:px-12 sm:py-7',
                )}
            >
                <div>
                    {isPage ? (
                        <InlineTitle
                            value={ticket.title}
                            onSave={(title) => save({ title })}
                        />
                    ) : (
                        <>
                            <SheetTitle asChild>
                                <InlineTitle
                                    value={ticket.title}
                                    onSave={(title) => save({ title })}
                                />
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Ticket #{ticket.number} details
                            </SheetDescription>
                        </>
                    )}
                </div>

                <dl
                    className={cn(
                        'grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-x-3 gap-y-1 text-sm',
                        layout !== 'side' &&
                            'md:grid-cols-[7.5rem_minmax(0,1fr)_7.5rem_minmax(0,1fr)]',
                    )}
                >
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
                                    {ticket.project_id
                                        ? 'A members-only project'
                                        : 'No project'}
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
                    {ticket.requester && (
                        <Property label="Requester">
                            <span className="flex min-w-0 items-center gap-2 px-2">
                                <span className="truncate">
                                    {[
                                        ticket.requester.name,
                                        ticket.requester.email,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </span>
                                <SoftBadge tone="info" className="shrink-0">
                                    via {ticket.requester.via}
                                </SoftBadge>
                            </span>
                        </Property>
                    )}
                    {ticket.contact && (
                        <Property label="Contact">
                            <span className="block truncate px-2">
                                {[
                                    ticket.contact.name,
                                    ticket.contact.email,
                                    ticket.contact.phone,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </span>
                        </Property>
                    )}
                    {ticket.location_name && (
                        <Property label="Location">
                            <span className="block truncate px-2">
                                {ticket.location_name}
                            </span>
                        </Property>
                    )}
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
                    {customFields.map((field) => (
                        <Property key={field.id} label={field.name}>
                            <InlineCustomFieldInput
                                field={field}
                                value={ticket.custom_fields[field.id]}
                                onChange={(value) =>
                                    save({
                                        custom_fields: { [field.id]: value },
                                    })
                                }
                            />
                        </Property>
                    ))}
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

                {!ticket.parent && (
                    <Subtasks ticket={ticket} onOpen={openTicket} />
                )}

                <section className="space-y-2">
                    <h3 className="text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                        Files
                    </h3>
                    <Attachments
                        attachments={ticket.attachments}
                        uploadUrl={storeAttachment.url(ticket.id)}
                        reload={['drawer']}
                        can={ticket.attachment_can}
                        canShare={Boolean(ticket.attachment_can.update)}
                    />
                </section>

                <LinkedArticles
                    target="ticket"
                    recordId={ticket.id}
                    articles={ticket.articles}
                    canEdit={Boolean(ticket.can.update)}
                    reload={['drawer']}
                />

                <LinkedRecords groups={ticket.linked_records} />

                <TicketConversation
                    storeUrl={storeComment.url(ticket.id)}
                    comments={ticket.comments}
                    activity={ticket.activity}
                    members={options.members}
                    reload={['drawer']}
                    reply={ticket.reply}
                />
            </div>
        </>
    );
}

function IconAction({
    label,
    onClick,
    className,
    children,
}: {
    label: string;
    onClick: () => void;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={cn('size-7', className)}
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
                className="h-control-md w-20 border-transparent bg-transparent px-2 shadow-none hover:border-border"
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

function Subtasks({
    ticket,
    onOpen,
}: {
    ticket: TicketDrawerData;
    onOpen: (id: string) => void;
}) {
    const { addSubtask, updateTicket } = useTicketActions();
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
                                onClick={() => onOpen(subtask.id)}
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
