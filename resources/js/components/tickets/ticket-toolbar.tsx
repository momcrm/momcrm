import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronDown,
    CircleDot,
    Columns3,
    FolderKanban,
    Kanban,
    LayoutList,
    ListFilter,
    Plus,
    Rows2,
    SlidersHorizontal,
    Table2,
    Tag,
    Trash2,
    UserRound,
    UsersRound,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { useLabels } from '@/components/labels';
import SearchInput from '@/components/search-input';
import {
    PriorityGlyph,
    useTicketPriorities,
    useTicketTypes,
} from '@/components/tickets/ticket-meta';
import { ticketTableColumnChoices } from '@/components/tickets/ticket-table';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type {
    TicketGroupBy,
    TicketViewPrefs,
} from '@/hooks/use-ticket-view-prefs';
import {
    customFieldFilterLabel,
    isFilterableCustomField,
    isSortableCustomField,
    useCustomFields,
} from '@/components/custom-fields/custom-field-input';
import { hueStyle } from '@/lib/colors';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import type { TicketFilters, TicketPickerOptions, TicketView } from '@/types';

const views: { value: TicketView; label: string; icon: LucideIcon }[] = [
    { value: 'list', label: 'List', icon: LayoutList },
    { value: 'board', label: 'Board', icon: Kanban },
    { value: 'table', label: 'Table', icon: Table2 },
];

const sorts: { value: string; label: string }[] = [
    { value: 'number', label: 'Number' },
    { value: 'priority', label: 'Priority' },
    { value: 'due_on', label: 'Due date' },
    { value: 'title', label: 'Title' },
    { value: 'status', label: 'Status' },
    { value: 'created_at', label: 'Created' },
    { value: 'updated_at', label: 'Last updated' },
];

const groupings: { value: TicketGroupBy; label: string }[] = [
    { value: 'status', label: 'Status' },
    { value: 'priority', label: 'Priority' },
    { value: 'assignee', label: 'Assignee' },
    { value: 'project', label: 'Project' },
    { value: 'none', label: 'None' },
];

const ANY = '__any__';
const ONLY = ['tickets', 'filters'];
const CALENDAR_ONLY = ['tickets', 'filters', 'calendar'];

type FieldKey =
    | 'status'
    | 'priority'
    | 'assignee'
    | 'project'
    | 'type'
    | 'group'
    | 'label'
    | `cf:${string}`;

type Field = {
    key: FieldKey;
    label: string;
    icon: ReactNode;
    /** What the filter is set to, or null for any. */
    value: string | null;
    /** Clearing puts the filter back to its default. */
    canClear: boolean;
    clear: () => void;
    /** The menu items that pick a value. */
    choices: ReactNode;
};

/**
 * The bars above the ticket views, as in gr8r: a search, Filter, Sort,
 * Group and Columns menus with the view switcher on the right, then a bar
 * of the filters in use. Filters and sort live in the URL; grouping and
 * columns are this browser's preference.
 *
 * Above the calendar (`forCalendar`) it is just the one bar: search, Filter
 * and Sort (the order within a day), with every status shown by default.
 */
export default function TicketToolbar({
    filters,
    options,
    prefs,
    inProject = false,
    fixedAssignee = false,
    forCalendar = false,
}: {
    filters: TicketFilters;
    options: TicketPickerOptions;
    /** The list's grouping and the table's columns, when the page offers them. */
    prefs?: TicketViewPrefs;
    /** On a project's page: the tabs pick the view and the project is fixed. */
    inProject?: boolean;
    /** On "My tickets": the assignee is fixed, so it isn't a filter. */
    fixedAssignee?: boolean;
    /** Above the calendar rather than a ticket list. */
    forCalendar?: boolean;
}) {
    const only = forCalendar ? CALENDAR_ONLY : ONLY;
    const [addedFields, setAddedFields] = useState<FieldKey[]>([]);
    const set = (changes: Record<string, string | string[] | null>) =>
        visitWithQuery(changes, { only });

    const priorities = useTicketPriorities(...filters.priority);
    const labels = useLabels(filters.label);
    const customFields = useCustomFields('ticket');
    const types = useTicketTypes(filters.type);
    const defaultStatus =
        forCalendar || filters.view === 'board' ? 'all' : 'open';

    const memberName = (id: string | null) =>
        id === 'me'
            ? 'Me'
            : id === 'none'
              ? 'Unassigned'
              : (options.members.find((member) => member.id === id)?.name ??
                null);
    const nameIn = (
        list: { id: string; name: string }[],
        id: string | null,
        none: string,
    ) =>
        id === 'none'
            ? none
            : (list.find((item) => item.id === id)?.name ?? null);

    const fields: Field[] = [
        {
            key: 'status',
            label: 'Status',
            icon: <CircleDot className="size-3.5" />,
            value:
                filters.status === 'open'
                    ? 'Open'
                    : filters.status === 'closed'
                      ? 'Closed'
                      : null,
            canClear: filters.status !== defaultStatus,
            clear: () => set({ status: null }),
            choices: (
                <DropdownMenuRadioGroup
                    value={filters.status}
                    onValueChange={(status) => set({ status })}
                >
                    <DropdownMenuRadioItem value="all">
                        Any
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="open">
                        Open
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="closed">
                        Closed
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
            ),
        },
        {
            key: 'priority',
            label: 'Priority',
            icon: (
                <PriorityGlyph
                    priority={priorities.find((priority) =>
                        filters.priority.includes(priority.id),
                    )}
                    className="size-3"
                />
            ),
            value: filters.priority.length
                ? filters.priority
                      .map(
                          (value) =>
                              priorities.find(
                                  (priority) => priority.id === value,
                              )?.name,
                      )
                      .join(', ')
                : null,
            canClear: filters.priority.length > 0,
            clear: () => set({ priority: null }),
            choices: priorities.map((priority) => (
                <DropdownMenuCheckboxItem
                    key={priority.id}
                    checked={filters.priority.includes(priority.id)}
                    onSelect={(event) => event.preventDefault()}
                    onCheckedChange={(checked) =>
                        set({
                            priority: checked
                                ? [...filters.priority, priority.id]
                                : filters.priority.filter(
                                      (value) => value !== priority.id,
                                  ),
                        })
                    }
                >
                    <PriorityGlyph priority={priority} />
                    {priority.name}
                </DropdownMenuCheckboxItem>
            )),
        },
        ...(!fixedAssignee
            ? [
                  {
                      key: 'assignee' as const,
                      label: 'Assignee',
                      icon: <UserRound className="size-3.5" />,
                      value: memberName(filters.assignee),
                      canClear: filters.assignee !== null,
                      clear: () => set({ assignee: null }),
                      choices: (
                          <RadioChoices
                              value={filters.assignee}
                              onChange={(assignee) => set({ assignee })}
                              choices={[
                                  { value: 'me', label: 'Me' },
                                  { value: 'none', label: 'Unassigned' },
                              ]}
                              more={options.members.map((member) => ({
                                  value: member.id,
                                  label: member.name,
                              }))}
                          />
                      ),
                  },
              ]
            : []),
        ...(!inProject
            ? [
                  {
                      key: 'project' as const,
                      label: 'Project',
                      icon: <FolderKanban className="size-3.5" />,
                      value: nameIn(
                          options.projects,
                          filters.project,
                          'No project',
                      ),
                      canClear: filters.project !== null,
                      clear: () => set({ project: null }),
                      choices: (
                          <RadioChoices
                              value={filters.project}
                              onChange={(project) => set({ project })}
                              choices={[{ value: 'none', label: 'No project' }]}
                              more={options.projects.map((project) => ({
                                  value: project.id,
                                  label: project.name,
                                  hue: project.hue,
                              }))}
                          />
                      ),
                  },
              ]
            : []),
        ...(types.length > 0
            ? [
                  {
                      key: 'type' as const,
                      label: 'Type',
                      icon: <Tag className="size-3.5" />,
                      value: nameIn(types, filters.type, 'No type'),
                      canClear: filters.type !== null,
                      clear: () => set({ type: null }),
                      choices: (
                          <RadioChoices
                              value={filters.type}
                              onChange={(type) => set({ type })}
                              choices={[{ value: 'none', label: 'No type' }]}
                              more={types.map((choice) => ({
                                  value: choice.id,
                                  label: choice.name,
                                  hue: choice.hue,
                              }))}
                          />
                      ),
                  },
              ]
            : []),
        ...(options.groups.length > 0
            ? [
                  {
                      key: 'group' as const,
                      label: 'Group',
                      icon: <UsersRound className="size-3.5" />,
                      value: nameIn(options.groups, filters.group, 'No group'),
                      canClear: filters.group !== null,
                      clear: () => set({ group: null }),
                      choices: (
                          <RadioChoices
                              value={filters.group}
                              onChange={(group) => set({ group })}
                              choices={[{ value: 'none', label: 'No group' }]}
                              more={options.groups.map((choice) => ({
                                  value: choice.id,
                                  label: choice.name,
                                  hue: choice.hue,
                              }))}
                          />
                      ),
                  },
              ]
            : []),
        ...(labels.length > 0
            ? [
                  {
                      key: 'label' as const,
                      label: 'Labels',
                      icon: <Tag className="size-3.5" />,
                      value: nameIn(labels, filters.label, 'No label'),
                      canClear: filters.label !== null,
                      clear: () => set({ label: null }),
                      choices: (
                          <RadioChoices
                              value={filters.label}
                              onChange={(label) => set({ label })}
                              choices={[]}
                              more={labels.map((label) => ({
                                  value: label.id,
                                  label: label.name,
                                  hue: label.hue,
                              }))}
                          />
                      ),
                  },
              ]
            : []),
        ...customFields.filter(isFilterableCustomField).map((field) => {
            const param = `cf[${field.id}]`;
            const value = filters.custom_fields[field.id];

            return {
                key: `cf:${field.id}` as const,
                label: field.name,
                icon: <SlidersHorizontal className="size-3.5" />,
                value: customFieldFilterLabel(field, value),
                canClear: value !== undefined,
                clear: () => set({ [param]: null }),
                choices: (
                    <RadioChoices
                        value={value ?? null}
                        onChange={(next) => set({ [param]: next })}
                        choices={
                            field.type === 'checkbox'
                                ? [
                                      { value: '1', label: 'Yes' },
                                      { value: '0', label: 'No' },
                                  ]
                                : [{ value: 'none', label: 'None' }]
                        }
                        more={field.options.map((option) => ({
                            value: option.id,
                            label: option.name,
                            hue: option.hue,
                        }))}
                    />
                ),
            };
        }),
    ];

    /** Status always shows; the rest once set or added from a menu. */
    const shown = fields.filter(
        (field) =>
            field.key === 'status' ||
            field.value !== null ||
            addedFields.includes(field.key),
    );
    const hidden = fields.filter((field) => !shown.includes(field));
    const activeCount = fields.filter((field) => field.canClear).length;
    const hasClearable = shown.some(
        (field) => field.canClear || field.key !== 'status',
    );

    const add = (key: FieldKey) => setAddedFields([...addedFields, key]);
    const remove = (field: Field) => {
        setAddedFields(addedFields.filter((key) => key !== field.key));

        if (field.canClear) {
            field.clear();
        }
    };
    const clearAll = () => {
        setAddedFields([]);
        set({
            status: null,
            priority: null,
            ...(fixedAssignee ? {} : { assignee: null }),
            label: null,
            type: null,
            group: null,
            ...(inProject ? {} : { project: null }),
        });
    };

    const view = forCalendar ? 'list' : filters.view;
    const customSorts = customFields
        .filter(isSortableCustomField)
        .map((field) => ({ value: `cf:${field.id}`, label: field.name }));
    const sortChoices = forCalendar
        ? sorts.filter((sort) => sort.value !== 'due_on')
        : [...sorts, ...customSorts];
    const sortLabel =
        view === 'board'
            ? 'Manual'
            : ([...sorts, ...customSorts].find(
                  (sort) => sort.value === filters.sort,
              )?.label ?? 'Number');

    return (
        <div>
            <div className="flex min-h-11 flex-wrap items-center gap-1 border-b px-4 py-2 md:px-6">
                <SearchInput
                    value={filters.search}
                    placeholder="Search tickets"
                    only={only}
                    size="sm"
                />

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <ToolbarButton isOn={activeCount > 0}>
                            <ListFilter className="size-3.5" />
                            Filter
                            {activeCount > 0 && (
                                <span className="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-[5px] bg-primary/10 px-1 text-xs font-medium text-primary">
                                    {activeCount}
                                </span>
                            )}
                        </ToolbarButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        className="w-[min(440px,calc(100vw-2rem))] p-0"
                    >
                        <div className="flex items-center justify-between px-3 pt-2.5 pb-1.5">
                            <span className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                Filter by
                            </span>
                            <button
                                type="button"
                                onClick={clearAll}
                                className="text-xs text-muted-foreground hover:text-foreground"
                            >
                                Clear all
                            </button>
                        </div>
                        <div className="flex flex-col gap-1.5 px-3 pb-2.5">
                            {shown.map((field, index) => (
                                <div
                                    key={field.key}
                                    className="grid grid-cols-[40px_minmax(0,108px)_20px_minmax(0,1fr)_24px] items-center gap-1.5 text-xs"
                                >
                                    <span className="text-muted-foreground">
                                        {index === 0 ? 'Where' : 'and'}
                                    </span>
                                    <span className="flex h-[26px] items-center gap-1.5 truncate rounded-md border bg-card px-2 text-foreground">
                                        <span className="text-subtle-foreground">
                                            {field.icon}
                                        </span>
                                        {field.label}
                                    </span>
                                    <span className="text-center text-muted-foreground">
                                        is
                                    </span>
                                    <DropdownMenuSub>
                                        <DropdownMenuSubTrigger className="h-[26px] rounded-md border bg-card px-2 py-0 text-xs data-[state=open]:bg-card [&>svg:last-child]:hidden">
                                            <span
                                                className={cn(
                                                    'min-w-0 flex-1 truncate',
                                                    field.value === null &&
                                                        'text-subtle-foreground',
                                                )}
                                            >
                                                {field.value ?? 'Select…'}
                                            </span>
                                            <ChevronDown className="size-3 text-subtle-foreground" />
                                        </DropdownMenuSubTrigger>
                                        <DropdownMenuSubContent className="max-h-72 w-52 overflow-y-auto">
                                            {field.choices}
                                        </DropdownMenuSubContent>
                                    </DropdownMenuSub>
                                    {field.key === 'status' &&
                                    !field.canClear ? (
                                        <span />
                                    ) : (
                                        <button
                                            type="button"
                                            aria-label={`Remove the ${field.label.toLowerCase()} filter`}
                                            onClick={() => remove(field)}
                                            className="flex size-6 items-center justify-center rounded-md text-subtle-foreground hover:bg-muted hover:text-foreground"
                                        >
                                            <Trash2 className="size-3.5" />
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                        {hidden.length > 0 && (
                            <div className="flex flex-wrap items-center gap-1 border-t px-3 py-2 text-xs text-muted-foreground">
                                <span className="mr-1">Add filter:</span>
                                {hidden.map((field) => (
                                    <button
                                        key={field.key}
                                        type="button"
                                        onClick={() => add(field.key)}
                                        className="inline-flex h-[22px] items-center gap-1 rounded-[5px] border bg-card px-1.5 text-soft-foreground hover:bg-muted hover:text-foreground"
                                    >
                                        <span className="text-subtle-foreground">
                                            {field.icon}
                                        </span>
                                        {field.label}
                                    </button>
                                ))}
                            </div>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <ToolbarButton>
                            <ArrowUpDown className="size-3.5" />
                            <span className="hidden sm:inline">Sort:</span>
                            {sortLabel}
                        </ToolbarButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" className="w-48">
                        <DropdownMenuLabel className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                            Sort by
                        </DropdownMenuLabel>
                        {view === 'board' ? (
                            <>
                                <DropdownMenuRadioGroup value="manual">
                                    <DropdownMenuRadioItem value="manual">
                                        Manual
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                                <p className="px-2 pt-1 pb-1.5 text-xs text-muted-foreground">
                                    The board keeps the order you drag cards
                                    into.
                                </p>
                            </>
                        ) : (
                            <>
                                <DropdownMenuRadioGroup
                                    value={filters.sort}
                                    onValueChange={(sort) => set({ sort })}
                                >
                                    {sortChoices.map((sort) => (
                                        <DropdownMenuRadioItem
                                            key={sort.value}
                                            value={sort.value}
                                        >
                                            {sort.label}
                                        </DropdownMenuRadioItem>
                                    ))}
                                </DropdownMenuRadioGroup>
                                <DropdownMenuSeparator />
                                <div className="grid grid-cols-2 gap-1 p-1">
                                    {(['asc', 'desc'] as const).map(
                                        (direction) => (
                                            <button
                                                key={direction}
                                                type="button"
                                                onClick={() =>
                                                    set({ direction })
                                                }
                                                className={cn(
                                                    'inline-flex h-[26px] items-center justify-center gap-1 rounded-md border text-xs',
                                                    filters.direction ===
                                                        direction
                                                        ? 'bg-card text-foreground shadow-xs'
                                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                                )}
                                            >
                                                {direction === 'asc' ? (
                                                    <ArrowUp className="size-3" />
                                                ) : (
                                                    <ArrowDown className="size-3" />
                                                )}
                                                {direction === 'asc'
                                                    ? 'Ascending'
                                                    : 'Descending'}
                                            </button>
                                        ),
                                    )}
                                </div>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>

                {prefs && view !== 'board' && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <ToolbarButton>
                                <Rows2 className="size-3.5" />
                                <span className="hidden sm:inline">Group:</span>
                                {
                                    groupings.find(
                                        (grouping) =>
                                            grouping.value === prefs.groupBy,
                                    )?.label
                                }
                            </ToolbarButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-44">
                            <DropdownMenuLabel className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                Group by
                            </DropdownMenuLabel>
                            <DropdownMenuRadioGroup
                                value={prefs.groupBy}
                                onValueChange={(groupBy) =>
                                    prefs.setGroupBy(groupBy as TicketGroupBy)
                                }
                            >
                                {groupings
                                    .filter(
                                        (grouping) =>
                                            !(
                                                fixedAssignee &&
                                                grouping.value === 'assignee'
                                            ),
                                    )
                                    .map((grouping) => (
                                        <DropdownMenuRadioItem
                                            key={grouping.value}
                                            value={grouping.value}
                                        >
                                            {grouping.label}
                                        </DropdownMenuRadioItem>
                                    ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}

                {prefs && view === 'table' && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <ToolbarButton>
                                <Columns3 className="size-3.5" />
                                Columns
                            </ToolbarButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-44">
                            <DropdownMenuLabel className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                Columns
                            </DropdownMenuLabel>
                            {ticketTableColumnChoices
                                .filter(
                                    (column) =>
                                        !(
                                            inProject &&
                                            column.key === 'project'
                                        ),
                                )
                                .map((column) => (
                                    <DropdownMenuCheckboxItem
                                        key={column.key}
                                        checked={
                                            !prefs.hiddenColumns.includes(
                                                column.key,
                                            )
                                        }
                                        onSelect={(event) =>
                                            event.preventDefault()
                                        }
                                        onCheckedChange={() =>
                                            prefs.toggleColumn(column.key)
                                        }
                                    >
                                        {column.label}
                                    </DropdownMenuCheckboxItem>
                                ))}
                            {customFields.length > 0 && (
                                <DropdownMenuSeparator />
                            )}
                            {customFields.map((field) => (
                                <DropdownMenuCheckboxItem
                                    key={field.id}
                                    checked={prefs.shownCustomFields.includes(
                                        field.id,
                                    )}
                                    onSelect={(event) => event.preventDefault()}
                                    onCheckedChange={() =>
                                        prefs.toggleCustomField(field.id)
                                    }
                                >
                                    {field.name}
                                </DropdownMenuCheckboxItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}

                <span className="flex-1" />

                {!inProject && !forCalendar && (
                    <nav
                        aria-label="View"
                        className="inline-flex gap-0.5 rounded-md bg-accent p-0.5"
                    >
                        {views.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                aria-pressed={view === option.value}
                                onClick={() =>
                                    view !== option.value &&
                                    visitWithQuery(
                                        {
                                            view:
                                                option.value === 'list'
                                                    ? null
                                                    : option.value,
                                            status: null,
                                            sort: null,
                                            direction: null,
                                        },
                                        { only: ONLY },
                                    )
                                }
                                className={cn(
                                    'inline-flex h-6 items-center gap-[5px] rounded-[5px] px-[9px] text-xs font-medium transition-colors',
                                    view === option.value
                                        ? 'bg-card text-foreground shadow-[0_1px_2px_rgb(0_0_0/0.08),0_0_0_1px_var(--border)]'
                                        : 'text-soft-foreground hover:text-foreground',
                                )}
                            >
                                <option.icon className="size-3.5" />
                                {option.label}
                            </button>
                        ))}
                    </nav>
                )}
            </div>

            {!forCalendar && (
                <div className="flex flex-wrap items-center gap-1.5 border-b px-4 py-2 text-xs text-muted-foreground md:px-6">
                    <ListFilter className="mr-0.5 size-3.5 text-subtle-foreground" />
                    {shown.map((field, index) => (
                        <span key={field.key} className="contents">
                            {index > 0 && <span>and</span>}
                            <FilterChip
                                field={field}
                                onRemove={
                                    field.key === 'status' && !field.canClear
                                        ? null
                                        : () => remove(field)
                                }
                            />
                        </span>
                    ))}
                    {hidden.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <ToolbarButton>
                                    <Plus className="size-3.5" />
                                    Add
                                </ToolbarButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" className="w-44">
                                {hidden.map((field) => (
                                    <DropdownMenuItem
                                        key={field.key}
                                        onSelect={() => add(field.key)}
                                    >
                                        <span className="text-subtle-foreground">
                                            {field.icon}
                                        </span>
                                        {field.label}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                    {hasClearable && (
                        <button
                            type="button"
                            onClick={clearAll}
                            className="ml-auto h-[26px] rounded-md px-2 text-xs text-soft-foreground hover:bg-muted hover:text-foreground"
                        >
                            Clear all
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

function ToolbarButton({
    isOn = false,
    className,
    children,
    ...props
}: ComponentProps<'button'> & { isOn?: boolean }) {
    return (
        <button
            type="button"
            className={cn(
                'inline-flex h-[26px] items-center gap-1.5 rounded-md px-2 text-xs font-medium whitespace-nowrap transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring',
                isOn
                    ? 'bg-accent text-foreground'
                    : 'text-soft-foreground hover:bg-muted hover:text-foreground data-[state=open]:bg-accent data-[state=open]:text-foreground',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}

function FilterChip({
    field,
    onRemove,
}: {
    field: Field;
    onRemove: (() => void) | null;
}) {
    return (
        <span className="inline-flex h-[26px] items-center gap-1 rounded-md border border-primary/40 bg-primary/10 pr-1 pl-2 text-xs text-foreground">
            <DropdownMenu>
                <DropdownMenuTrigger className="flex h-full items-center gap-1.5 outline-none">
                    <span className="text-soft-foreground">{field.icon}</span>
                    <b className="font-medium text-soft-foreground">
                        {field.label}
                    </b>
                    <span>is</span>
                    <span className="max-w-48 truncate">
                        {field.value ?? 'any'}
                    </span>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    className="max-h-72 w-52 overflow-y-auto"
                >
                    {field.choices}
                </DropdownMenuContent>
            </DropdownMenu>
            {onRemove ? (
                <button
                    type="button"
                    aria-label={`Remove the ${field.label.toLowerCase()} filter`}
                    onClick={onRemove}
                    className="flex size-[18px] items-center justify-center rounded text-soft-foreground hover:bg-primary/10 hover:text-foreground"
                >
                    <X className="size-3" />
                </button>
            ) : (
                <span className="w-1" />
            )}
        </span>
    );
}

function RadioChoices({
    value,
    onChange,
    choices,
    more,
}: {
    value: string | null;
    onChange: (value: string | null) => void;
    choices: { value: string; label: string }[];
    more: { value: string; label: string; hue?: number | null }[];
}) {
    return (
        <DropdownMenuRadioGroup
            value={value ?? ANY}
            onValueChange={(next) => onChange(next === ANY ? null : next)}
        >
            <DropdownMenuRadioItem value={ANY}>Any</DropdownMenuRadioItem>
            {choices.map((choice) => (
                <DropdownMenuRadioItem key={choice.value} value={choice.value}>
                    {choice.label}
                </DropdownMenuRadioItem>
            ))}
            {more.length > 0 && <DropdownMenuSeparator />}
            {more.map((choice) => (
                <DropdownMenuRadioItem key={choice.value} value={choice.value}>
                    {choice.hue !== undefined && (
                        <span
                            aria-hidden
                            className="size-2 rounded-[3px] hue-dot"
                            style={hueStyle(choice.hue)}
                        />
                    )}
                    {choice.label}
                </DropdownMenuRadioItem>
            ))}
        </DropdownMenuRadioGroup>
    );
}
