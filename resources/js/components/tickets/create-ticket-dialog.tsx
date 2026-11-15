import { Link, router, useHttp, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    CalendarPlus,
    ChevronLeft,
    ChevronRight,
    Circle,
    MapPin,
    Paperclip,
    Plus,
    Tag,
    UserRound,
    X,
} from 'lucide-react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import {
    defaultStatusId,
    PriorityGlyph,
    useTicketPriorities,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/hooks/use-initials';
import { colorDot, colorText } from '@/lib/colors';
import { addDays, addMonths, parseDay, toDay } from '@/lib/dates';
import { formatBytes } from '@/lib/format';
import { cn } from '@/lib/utils';
import { create as createCustomer } from '@/routes/customers';
import { formOptions, store } from '@/routes/tickets';
import type { TicketFormOptions } from '@/types';

type Defaults = {
    customerId?: string | null;
    projectId?: string | null;
    statusId?: string;
};

const CreateTicketContext = createContext<{
    openCreateTicket: (defaults?: Defaults) => void;
} | null>(null);

/**
 * Opens the new-ticket dialog from anywhere inside the app layout, optionally
 * starting it for a customer, a project or a status.
 */
export function useCreateTicket(): {
    openCreateTicket: (defaults?: Defaults) => void;
} {
    const context = useContext(CreateTicketContext);

    if (!context) {
        throw new Error(
            'useCreateTicket must be used inside CreateTicketProvider.',
        );
    }

    return context;
}

/**
 * Holds the new-ticket dialog. Its choices are fetched each time it opens, so
 * a customer or label added a moment ago is already there.
 */
export function CreateTicketProvider({ children }: { children: ReactNode }) {
    const [defaults, setDefaults] = useState<Defaults | null>(null);
    const [options, setOptions] = useState<TicketFormOptions | null>(null);
    const [openCount, setOpenCount] = useState(0);
    const http = useHttp<Record<string, never>, TicketFormOptions>();

    const openCreateTicket = useCallback(
        (next: Defaults = {}) => {
            setDefaults(next);
            setOpenCount((count) => count + 1);
            http.cancel();
            void http
                .get(formOptions.url())
                .then(setOptions)
                .catch(() => undefined);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const context = useMemo(() => ({ openCreateTicket }), [openCreateTicket]);

    return (
        <CreateTicketContext.Provider value={context}>
            {children}
            <Dialog
                open={defaults !== null}
                onOpenChange={(open) => !open && setDefaults(null)}
            >
                <DialogContent className="top-[10vh] max-h-[85vh] translate-y-0 gap-0 overflow-y-auto p-0 shadow-pop sm:max-w-[34rem] [&>button:last-child]:top-4.5">
                    <DialogTitle className="px-4 pt-4 pb-3 text-base">
                        New ticket
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        Log a request or job so your team can pick it up.
                    </DialogDescription>
                    {defaults !== null &&
                        (options === null ? (
                            <LoadingBody />
                        ) : options.customers.length === 0 &&
                          options.projects.length === 0 ? (
                            <NothingToFileUnder
                                onDone={() => setDefaults(null)}
                            />
                        ) : (
                            <CreateTicketForm
                                key={openCount}
                                options={options}
                                defaults={defaults}
                                onDone={() => setDefaults(null)}
                            />
                        ))}
                </DialogContent>
            </Dialog>
        </CreateTicketContext.Provider>
    );
}

function LoadingBody() {
    return (
        <div className="space-y-3 px-4 pb-4">
            <Skeleton className="h-9 w-full" />
            <Skeleton className="h-20 w-full" />
            <div className="flex gap-2">
                <Skeleton className="h-7 w-24" />
                <Skeleton className="h-7 w-20" />
                <Skeleton className="h-7 w-24" />
            </div>
        </div>
    );
}

function NothingToFileUnder({ onDone }: { onDone: () => void }) {
    return (
        <div className="space-y-3 px-4 pb-4 text-sm text-muted-foreground">
            <p>Every ticket is for a customer or a project. Add one first.</p>
            <Button size="sm" asChild onClick={onDone}>
                <Link href={createCustomer()}>Add customer</Link>
            </Button>
        </div>
    );
}

const chipClass =
    'inline-flex h-7 max-w-full min-w-0 items-center gap-1.5 rounded-md border bg-card px-2 text-[13px] text-foreground hover:bg-accent/60 data-[state=open]:bg-accent/60';

function CreateTicketForm({
    options,
    defaults,
    onDone,
}: {
    options: TicketFormOptions;
    defaults: Defaults;
    onDone: () => void;
}) {
    const currentUserId = usePage().props.auth.user?.id as string | undefined;
    const initialProject = defaults.projectId ?? '';
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [projectId, setProjectId] = useState(initialProject);
    const [customerId, setCustomerId] = useState(
        defaults.customerId ??
            options.projects.find((project) => project.id === initialProject)
                ?.customer_id ??
            '',
    );
    const [contactId, setContactId] = useState('');
    const [locationId, setLocationId] = useState('');
    const statuses = useTicketStatuses();
    const [statusId, setStatusId] = useState(
        defaults.statusId ?? defaultStatusId(statuses, 'open') ?? '',
    );
    const status = statuses.find((option) => option.id === statusId);
    const [assigneeId, setAssigneeId] = useState(
        options.members.some((member) => member.id === currentUserId)
            ? (currentUserId ?? '')
            : '',
    );
    const priorities = useTicketPriorities();
    const [priorityId, setPriorityId] = useState(
        priorities.find((option) => option.is_default)?.id ?? '',
    );
    const priority = priorities.find((option) => option.id === priorityId);
    const [startsOn, setStartsOn] = useState<string | null>(null);
    const [dueOn, setDueOn] = useState<string | null>(null);
    const [labelIds, setLabelIds] = useState<string[]>([]);
    const [subtasks, setSubtasks] = useState<string[]>([]);
    const [subtaskDraft, setSubtaskDraft] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const [createMore, setCreateMore] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const fileInput = useRef<HTMLInputElement>(null);
    const titleInput = useRef<HTMLInputElement>(null);

    const project = options.projects.find((option) => option.id === projectId);
    const contacts = options.contacts.filter(
        (contact) => contact.customer_id === customerId,
    );
    const locations = options.locations.filter(
        (location) => location.customer_id === customerId,
    );

    const chooseProject = (id: string) => {
        setProjectId(id);

        const projectCustomer = options.projects.find(
            (option) => option.id === id,
        )?.customer_id;

        if (projectCustomer && !customerId) {
            chooseCustomer(projectCustomer);
        }
    };

    const chooseCustomer = (id: string) => {
        setCustomerId(id);
        setContactId('');
        setLocationId('');
    };

    const addSubtask = () => {
        if (subtaskDraft.trim()) {
            setSubtasks([...subtasks, subtaskDraft.trim()]);
            setSubtaskDraft('');
        }
    };

    const submit = () => {
        if (processing || !title.trim()) {
            titleInput.current?.focus();

            return;
        }

        const pendingSubtask = subtaskDraft.trim();

        router.post(
            store.url(),
            {
                title: title.trim(),
                description: description || null,
                project_id: projectId || null,
                customer_id: customerId || null,
                contact_id: contactId || null,
                location_id: locationId || null,
                assignee_id: assigneeId || null,
                status_id: statusId || null,
                priority_id: priorityId || null,
                starts_on: startsOn,
                due_on: dueOn,
                label_ids: labelIds,
                subtasks: pendingSubtask
                    ? [...subtasks, pendingSubtask]
                    : subtasks,
                ...(files.length > 0 ? { files } : {}),
                quick: 1,
            },
            {
                forceFormData: files.length > 0,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setErrors,
                onSuccess: () => {
                    if (!createMore) {
                        onDone();

                        return;
                    }

                    setErrors({});
                    setTitle('');
                    setDescription('');
                    setSubtasks([]);
                    setSubtaskDraft('');
                    setFiles([]);
                    titleInput.current?.focus();
                },
            },
        );
    };

    const submitOnModEnter = (event: KeyboardEvent) => {
        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            submit();
        }
    };

    const otherErrors = Object.entries(errors)
        .filter(([field]) => field !== 'title')
        .map(([, message]) => message);
    const assignee = options.members.find((member) => member.id === assigneeId);
    const customer = options.customers.find(
        (option) => option.id === customerId,
    );
    const selectedLabels = options.labels.filter((label) =>
        labelIds.includes(label.id),
    );

    return (
        <form
            onKeyDown={submitOnModEnter}
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
        >
            <div className="space-y-3 px-4 pb-4">
                <div>
                    <input
                        ref={titleInput}
                        value={title}
                        onChange={(event) => setTitle(event.target.value)}
                        placeholder="Ticket title"
                        aria-label="Title"
                        aria-invalid={Boolean(errors.title)}
                        autoFocus
                        className="h-10 w-full rounded-md border bg-card px-3 text-[15px] font-medium outline-none placeholder:font-normal placeholder:text-subtle-foreground focus:border-ring focus:ring-[3px] focus:ring-ring/20 aria-invalid:border-destructive"
                    />
                    {errors.title && (
                        <p className="mt-1 text-xs text-destructive">
                            {errors.title}
                        </p>
                    )}
                </div>

                <textarea
                    value={description}
                    onChange={(event) => setDescription(event.target.value)}
                    placeholder="Add details… (optional)"
                    aria-label="Details"
                    rows={3}
                    className="w-full resize-y rounded-md border bg-card px-3 py-2 text-sm outline-none placeholder:text-subtle-foreground focus:border-ring focus:ring-[3px] focus:ring-ring/20"
                />

                <div className="flex flex-wrap gap-1.5">
                    <SearchPicker
                        label="Project"
                        placeholder="Move to project…"
                        value={projectId}
                        empty="No project"
                        choices={options.projects.map((option) => ({
                            value: option.id,
                            label: option.name,
                            dot: colorDot[option.color],
                        }))}
                        onChange={chooseProject}
                    >
                        {project ? (
                            <>
                                <span
                                    aria-hidden
                                    className={cn(
                                        'size-2 shrink-0 rounded-[3px]',
                                        colorDot[project.color],
                                    )}
                                />
                                <span className="truncate">{project.name}</span>
                            </>
                        ) : (
                            <Placeholder>No project</Placeholder>
                        )}
                    </SearchPicker>

                    <SearchPicker
                        label="Customer"
                        placeholder="Choose customer…"
                        value={customerId}
                        empty="No customer"
                        choices={options.customers.map((option) => ({
                            value: option.id,
                            label: option.name,
                        }))}
                        onChange={chooseCustomer}
                    >
                        <Building2 className="size-3.5 shrink-0 text-muted-foreground" />
                        {customer ? (
                            <span className="truncate">{customer.name}</span>
                        ) : (
                            <Placeholder>Customer</Placeholder>
                        )}
                    </SearchPicker>

                    <SearchPicker
                        label="Status"
                        value={statusId}
                        choices={statuses.map((option) => ({
                            value: option.id,
                            label: option.name,
                            dot: colorDot[option.color],
                        }))}
                        onChange={setStatusId}
                    >
                        <Circle
                            className={cn(
                                'size-3.5 shrink-0',
                                status
                                    ? colorText[status.color]
                                    : 'text-muted-foreground',
                            )}
                        />
                        {status?.name ?? 'Status'}
                    </SearchPicker>

                    <SearchPicker
                        label="Assignee"
                        placeholder="Assign to…"
                        value={assigneeId}
                        empty="Unassigned"
                        choices={options.members.map((member) => ({
                            value: member.id,
                            label: member.name,
                        }))}
                        onChange={setAssigneeId}
                    >
                        <Initials name={assignee?.name ?? null} />
                        {assignee ? (
                            <span className="truncate">{assignee.name}</span>
                        ) : (
                            <Placeholder>Unassigned</Placeholder>
                        )}
                    </SearchPicker>

                    <SearchPicker
                        label="Priority"
                        value={priorityId}
                        choices={priorities.map((option) => ({
                            value: option.id,
                            label: option.name,
                        }))}
                        onChange={setPriorityId}
                    >
                        <PriorityGlyph priority={priority} />
                        {priority?.name ?? 'Priority'}
                    </SearchPicker>

                    <DatePicker
                        label="Due date"
                        icon={CalendarDays}
                        value={dueOn}
                        onChange={setDueOn}
                    />
                    <DatePicker
                        label="Start date"
                        icon={CalendarPlus}
                        value={startsOn}
                        onChange={setStartsOn}
                    />

                    <LabelPicker
                        options={options.labels}
                        selected={labelIds}
                        onChange={setLabelIds}
                    >
                        <Tag className="size-3.5 shrink-0 text-muted-foreground" />
                        {selectedLabels.length === 0 ? (
                            <Placeholder>Labels</Placeholder>
                        ) : (
                            <span className="flex items-center gap-1">
                                {selectedLabels.map((label) => (
                                    <span
                                        key={label.id}
                                        className="inline-flex items-center gap-1"
                                    >
                                        <span
                                            aria-hidden
                                            className={cn(
                                                'size-1.5 rounded-full',
                                                colorDot[label.color],
                                            )}
                                        />
                                        {label.name}
                                    </span>
                                ))}
                            </span>
                        )}
                    </LabelPicker>

                    {contacts.length > 0 && (
                        <SearchPicker
                            label="Contact"
                            placeholder="Choose contact…"
                            value={contactId}
                            empty="No contact"
                            choices={contacts.map((contact) => ({
                                value: contact.id,
                                label: contact.name,
                            }))}
                            onChange={setContactId}
                        >
                            <UserRound className="size-3.5 shrink-0 text-muted-foreground" />
                            {contactId ? (
                                <span className="truncate">
                                    {
                                        contacts.find(
                                            (contact) =>
                                                contact.id === contactId,
                                        )?.name
                                    }
                                </span>
                            ) : (
                                <Placeholder>Contact</Placeholder>
                            )}
                        </SearchPicker>
                    )}

                    {locations.length > 0 && (
                        <SearchPicker
                            label="Location"
                            placeholder="Choose location…"
                            value={locationId}
                            empty="No particular location"
                            choices={locations.map((location) => ({
                                value: location.id,
                                label: location.name,
                            }))}
                            onChange={setLocationId}
                        >
                            <MapPin className="size-3.5 shrink-0 text-muted-foreground" />
                            {locationId ? (
                                <span className="truncate">
                                    {
                                        locations.find(
                                            (location) =>
                                                location.id === locationId,
                                        )?.name
                                    }
                                </span>
                            ) : (
                                <Placeholder>Location</Placeholder>
                            )}
                        </SearchPicker>
                    )}
                </div>

                <section className="space-y-1.5">
                    <h3 className="text-sm font-medium">Subtasks</h3>
                    {subtasks.length > 0 && (
                        <ul className="space-y-0.5">
                            {subtasks.map((subtask, index) => (
                                <li
                                    key={`${index}-${subtask}`}
                                    className="group flex h-8 items-center gap-2 rounded-md px-2 text-sm hover:bg-accent/60"
                                >
                                    <span className="size-3.5 shrink-0 rounded-full border border-border-strong" />
                                    <span className="min-w-0 flex-1 truncate">
                                        {subtask}
                                    </span>
                                    <button
                                        type="button"
                                        aria-label={`Remove ${subtask}`}
                                        onClick={() =>
                                            setSubtasks(
                                                subtasks.filter(
                                                    (_, other) =>
                                                        other !== index,
                                                ),
                                            )
                                        }
                                        className="text-subtle-foreground opacity-0 group-hover:opacity-100 hover:text-foreground focus-visible:opacity-100"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <label className="flex h-9 items-center gap-2 rounded-md border bg-card px-3 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/20">
                        <Plus className="size-4 shrink-0 text-subtle-foreground" />
                        <input
                            value={subtaskDraft}
                            onChange={(event) =>
                                setSubtaskDraft(event.target.value)
                            }
                            onKeyDown={(event) => {
                                if (
                                    event.key === 'Enter' &&
                                    !event.metaKey &&
                                    !event.ctrlKey
                                ) {
                                    event.preventDefault();
                                    addSubtask();
                                }
                            }}
                            placeholder="Add a subtask and press Enter"
                            aria-label="New subtask"
                            className="h-full flex-1 bg-transparent text-sm outline-none placeholder:text-subtle-foreground"
                        />
                    </label>
                </section>

                <section className="space-y-1.5">
                    <h3 className="text-sm font-medium">Attachments</h3>
                    {files.length > 0 && (
                        <ul className="space-y-0.5">
                            {files.map((file, index) => (
                                <li
                                    key={`${index}-${file.name}`}
                                    className="flex h-8 items-center gap-2 rounded-md px-2 text-sm hover:bg-accent/60"
                                >
                                    <Paperclip className="size-3.5 shrink-0 text-subtle-foreground" />
                                    <span className="min-w-0 flex-1 truncate">
                                        {file.name}
                                    </span>
                                    <span className="text-xs text-subtle-foreground tabular-nums">
                                        {formatBytes(file.size)}
                                    </span>
                                    <button
                                        type="button"
                                        aria-label={`Remove ${file.name}`}
                                        onClick={() =>
                                            setFiles(
                                                files.filter(
                                                    (_, other) =>
                                                        other !== index,
                                                ),
                                            )
                                        }
                                        className="text-subtle-foreground hover:text-foreground"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <button
                        type="button"
                        onClick={() => fileInput.current?.click()}
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={(event) => {
                            event.preventDefault();
                            setFiles([
                                ...files,
                                ...Array.from(event.dataTransfer.files),
                            ]);
                        }}
                        className="flex h-10 w-full items-center justify-center gap-2 rounded-md border border-dashed text-sm text-muted-foreground hover:bg-accent/40 hover:text-foreground"
                    >
                        <Paperclip className="size-4" />
                        Attach files
                    </button>
                    <input
                        ref={fileInput}
                        type="file"
                        multiple
                        hidden
                        onChange={(event) => {
                            setFiles([
                                ...files,
                                ...Array.from(event.target.files ?? []),
                            ]);
                            event.target.value = '';
                        }}
                    />
                </section>

                {otherErrors.length > 0 && (
                    <ul className="space-y-0.5 text-xs text-destructive">
                        {[...new Set(otherErrors)].map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="flex items-center gap-2 border-t px-4 py-3">
                <label className="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                    <button
                        type="button"
                        role="switch"
                        aria-checked={createMore}
                        onClick={() => setCreateMore(!createMore)}
                        className={cn(
                            'relative h-4.5 w-8 shrink-0 rounded-full transition-colors',
                            createMore
                                ? 'bg-primary'
                                : 'bg-muted-foreground/25',
                        )}
                    >
                        <span
                            className={cn(
                                'absolute top-0.5 left-0.5 size-3.5 rounded-full bg-white shadow-xs transition-transform',
                                createMore && 'translate-x-3.5',
                            )}
                        />
                    </button>
                    Create more
                </label>
                <span className="ml-auto hidden items-center gap-1 sm:flex">
                    <kbd className="flex h-5 min-w-5 items-center justify-center rounded-sm border bg-muted px-1 font-mono text-[10px] text-muted-foreground">
                        {isMac ? '⌘' : 'Ctrl'}
                    </kbd>
                    <kbd className="flex h-5 min-w-5 items-center justify-center rounded-sm border bg-muted px-1 font-mono text-[10px] text-muted-foreground">
                        ↵
                    </kbd>
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="max-sm:ml-auto"
                    onClick={onDone}
                >
                    Cancel
                </Button>
                <Button
                    size="sm"
                    disabled={processing}
                    data-test="save-ticket-button"
                >
                    {processing && <Spinner />}
                    Create ticket
                </Button>
            </div>
        </form>
    );
}

const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iP(hone|ad)/.test(navigator.platform);

function Placeholder({ children }: { children: ReactNode }) {
    return <span className="text-muted-foreground">{children}</span>;
}

function Initials({ name }: { name: string | null }) {
    const getInitials = useInitials();

    if (!name) {
        return (
            <span className="size-4 shrink-0 rounded-full border border-dashed border-border-strong" />
        );
    }

    return (
        <span className="inline-flex size-4 shrink-0 items-center justify-center rounded-full bg-accent text-[8px] font-semibold text-muted-foreground">
            {getInitials(name)}
        </span>
    );
}

/**
 * A chip that opens a list of choices, with a filter box when given a
 * placeholder.
 */
function SearchPicker({
    label,
    placeholder,
    value,
    empty,
    choices,
    onChange,
    children,
}: {
    label: string;
    placeholder?: string;
    value: string;
    empty?: string;
    choices: { value: string; label: string; dot?: string }[];
    onChange: (value: string) => void;
    children: ReactNode;
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState('');
    const filterInput = useRef<HTMLInputElement>(null);
    const matching = choices.filter((choice) =>
        choice.label.toLowerCase().includes(query.trim().toLowerCase()),
    );
    const allChoices =
        empty !== undefined && !query
            ? [{ value: '', label: empty }, ...matching]
            : matching;

    return (
        <DropdownMenu
            open={isOpen}
            onOpenChange={(open) => {
                setIsOpen(open);

                if (open) {
                    requestAnimationFrame(() => filterInput.current?.focus());
                } else {
                    setQuery('');
                }
            }}
        >
            <DropdownMenuTrigger className={chipClass} aria-label={label}>
                {children}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="max-h-72 w-60 overflow-y-auto"
            >
                {placeholder && (
                    <>
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Escape') {
                                    return;
                                }

                                event.stopPropagation();

                                if (event.key === 'ArrowDown') {
                                    event.preventDefault();
                                    event.currentTarget
                                        .closest('[role="menu"]')
                                        ?.querySelector<HTMLElement>(
                                            '[role="menuitem"]',
                                        )
                                        ?.focus();
                                }

                                if (event.key === 'Enter') {
                                    event.preventDefault();

                                    if (allChoices[0]) {
                                        onChange(allChoices[0].value);
                                        setIsOpen(false);
                                    }
                                }
                            }}
                            placeholder={placeholder}
                            aria-label={`Filter ${label.toLowerCase()}`}
                            ref={filterInput}
                            className="h-8 w-full bg-transparent px-2 text-sm outline-none placeholder:text-subtle-foreground"
                        />
                        <DropdownMenuSeparator />
                    </>
                )}
                {allChoices.length === 0 && (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">
                        No matches.
                    </p>
                )}
                {allChoices.map((choice) => (
                    <DropdownMenuItem
                        key={choice.value || '__none__'}
                        onSelect={() => onChange(choice.value)}
                        className="justify-between"
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            {choice.dot && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'size-2 shrink-0 rounded-[3px]',
                                        choice.dot,
                                    )}
                                />
                            )}
                            <span className="truncate">{choice.label}</span>
                        </span>
                        {choice.value === value && (
                            <span className="text-primary">✓</span>
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function LabelPicker({
    options,
    selected,
    onChange,
    children,
}: {
    options: TicketFormOptions['labels'];
    selected: string[];
    onChange: (ids: string[]) => void;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger className={chipClass} aria-label="Labels">
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
                            className={cn(
                                'size-2 rounded-full',
                                colorDot[label.color],
                            )}
                        />
                        {label.name}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
});
const chipDayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});
const weekdays = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

/**
 * A date chip opening quick choices and a month calendar.
 */
function DatePicker({
    label,
    icon: Icon,
    value,
    onChange,
}: {
    label: string;
    icon: typeof CalendarDays;
    value: string | null;
    onChange: (value: string | null) => void;
}) {
    const today = toDay(new Date());
    const [month, setMonth] = useState(() => addMonths(value ?? today, 0));
    const first = parseDay(month);
    const leading = (first.getDay() + 6) % 7;
    const days = Array.from({ length: 42 }, (_, index) =>
        addDays(month, index - leading),
    );
    const quick = [
        { label: 'Today', day: today },
        { label: 'Tomorrow', day: addDays(today, 1) },
        { label: 'Next week', day: addDays(today, 7) },
        { label: 'In 2 weeks', day: addDays(today, 14) },
    ];

    return (
        <DropdownMenu
            onOpenChange={(open) =>
                open && setMonth(addMonths(value ?? today, 0))
            }
        >
            <DropdownMenuTrigger className={chipClass} aria-label={label}>
                <Icon className="size-3.5 shrink-0 text-muted-foreground" />
                {value ? (
                    chipDayFormatter.format(parseDay(value))
                ) : (
                    <Placeholder>{label}</Placeholder>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-72 p-2">
                <div className="mb-2 grid grid-cols-4 gap-1">
                    {quick.map((choice) => (
                        <DropdownMenuItem
                            key={choice.label}
                            onSelect={() => onChange(choice.day)}
                            className="justify-center px-1 text-xs whitespace-nowrap"
                        >
                            {choice.label}
                        </DropdownMenuItem>
                    ))}
                </div>
                <div className="flex items-center justify-between px-1 pb-1">
                    <span className="text-sm font-medium">
                        {monthFormatter.format(first)}
                    </span>
                    <span className="flex">
                        <button
                            type="button"
                            aria-label="Previous month"
                            onClick={() => setMonth(addMonths(month, -1))}
                            className="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            <ChevronLeft className="size-4" />
                        </button>
                        <button
                            type="button"
                            aria-label="Next month"
                            onClick={() => setMonth(addMonths(month, 1))}
                            className="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            <ChevronRight className="size-4" />
                        </button>
                    </span>
                </div>
                <div className="grid grid-cols-7 text-center text-[11px] text-subtle-foreground">
                    {weekdays.map((weekday) => (
                        <span key={weekday} className="py-1">
                            {weekday}
                        </span>
                    ))}
                </div>
                <div className="grid grid-cols-7 gap-0.5">
                    {days.map((day) => {
                        const inMonth = day.slice(0, 7) === month.slice(0, 7);

                        return (
                            <DropdownMenuItem
                                key={day}
                                onSelect={() => onChange(day)}
                                aria-label={day}
                                className={cn(
                                    'h-7 justify-center p-0 text-xs tabular-nums',
                                    !inMonth && 'text-subtle-foreground',
                                    day === today &&
                                        'font-semibold text-primary',
                                    day === value &&
                                        'bg-primary text-primary-foreground focus:bg-primary focus:text-primary-foreground',
                                )}
                            >
                                {parseDay(day).getDate()}
                            </DropdownMenuItem>
                        );
                    })}
                </div>
                {value && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onSelect={() => onChange(null)}
                            className="text-muted-foreground"
                        >
                            <X />
                            Clear {label.toLowerCase()}
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
