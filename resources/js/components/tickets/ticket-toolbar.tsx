import { Columns3, List, Plus, Table2, X } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import SearchInput from '@/components/search-input';
import {
    PriorityGlyph,
    useTicketLabels,
    useTicketPriorities,
} from '@/components/tickets/ticket-meta';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

import { visitWithQuery } from '@/lib/query';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { TicketFilters, TicketPickerOptions, TicketView } from '@/types';

const views: { value: TicketView; label: string; icon: LucideIcon }[] = [
    { value: 'list', label: 'List', icon: List },
    { value: 'board', label: 'Board', icon: Columns3 },
    { value: 'table', label: 'Table', icon: Table2 },
];

const statusFilters: { value: TicketFilters['status']; label: string }[] = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' },
];

const ANY = '__any__';
const ONLY = ['tickets', 'filters'];

/**
 * The bar above the ticket views: which view, a search, open/closed and
 * filter chips. Every choice lives in the URL.
 */
export default function TicketToolbar({
    filters,
    options,
    inProject = false,
}: {
    filters: TicketFilters;
    options: TicketPickerOptions;
    /** On a project's page: the tabs pick the view and the project is fixed. */
    inProject?: boolean;
}) {
    const set = (changes: Record<string, string | string[] | null>) =>
        visitWithQuery(changes, { only: ONLY });

    const memberName = (id: string | null) =>
        id === 'me'
            ? 'Me'
            : id === 'none'
              ? 'Unassigned'
              : options.members.find((member) => member.id === id)?.name;
    const projectName = (id: string | null) =>
        id === 'none'
            ? 'No project'
            : options.projects.find((project) => project.id === id)?.name;
    const priorities = useTicketPriorities(...filters.priority);
    const labels = useTicketLabels(filters.label);
    const labelName = (id: string | null) =>
        labels.find((label) => label.id === id)?.name;

    const hasFilters =
        filters.priority.length > 0 ||
        filters.assignee !== null ||
        (!inProject && filters.project !== null) ||
        filters.label !== null;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                {!inProject && (
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        size="sm"
                        value={filters.view}
                        onValueChange={(view) =>
                            view &&
                            visitWithQuery(
                                {
                                    view: view === 'list' ? null : view,
                                    status: null,
                                    sort: null,
                                    direction: null,
                                },
                                { only: ONLY },
                            )
                        }
                        aria-label="View"
                        className="bg-card"
                    >
                        {views.map((view) => (
                            <ToggleGroupItem
                                key={view.value}
                                value={view.value}
                                aria-label={view.label}
                                className="gap-1.5 px-2.5"
                            >
                                <view.icon className="size-3.5" />
                                {view.label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                )}

                <nav
                    aria-label="Open or closed"
                    className="flex gap-0.5 rounded-md bg-muted p-0.5"
                >
                    {statusFilters.map((status) => (
                        <button
                            key={status.value}
                            type="button"
                            onClick={() => set({ status: status.value })}
                            className={cn(
                                'rounded-[5px] px-2.5 py-0.5 text-sm',
                                filters.status === status.value
                                    ? 'bg-card shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {status.label}
                        </button>
                    ))}
                </nav>

                <div className="ml-auto w-full sm:w-64">
                    <SearchInput
                        value={filters.search}
                        placeholder="Search tickets"
                        only={ONLY}
                    />
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-1.5">
                <FilterChip
                    label="Priority"
                    value={
                        filters.priority.length
                            ? filters.priority
                                  .map(
                                      (value) =>
                                          priorities.find(
                                              (priority) =>
                                                  priority.id === value,
                                          )?.name,
                                  )
                                  .join(', ')
                            : null
                    }
                    onClear={() => set({ priority: null })}
                >
                    {priorities.map((priority) => (
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
                    ))}
                </FilterChip>

                <FilterChip
                    label="Assignee"
                    value={memberName(filters.assignee) ?? null}
                    onClear={() => set({ assignee: null })}
                >
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
                </FilterChip>

                {!inProject && (
                    <FilterChip
                        label="Project"
                        value={projectName(filters.project) ?? null}
                        onClear={() => set({ project: null })}
                    >
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
                    </FilterChip>
                )}

                {labels.length > 0 && (
                    <FilterChip
                        label="Label"
                        value={labelName(filters.label) ?? null}
                        onClear={() => set({ label: null })}
                    >
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
                    </FilterChip>
                )}

                {hasFilters && (
                    <button
                        type="button"
                        onClick={() =>
                            set({
                                priority: null,
                                assignee: null,
                                label: null,
                                ...(inProject ? {} : { project: null }),
                            })
                        }
                        className="px-1.5 text-xs text-muted-foreground hover:text-foreground"
                    >
                        Clear all
                    </button>
                )}
            </div>
        </div>
    );
}

function FilterChip({
    label,
    value,
    onClear,
    children,
}: {
    label: string;
    value: string | null;
    onClear: () => void;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex h-7 items-center rounded-md border text-xs',
                value
                    ? 'border-primary/30 bg-primary/8 text-foreground'
                    : 'border-dashed bg-card text-muted-foreground',
            )}
        >
            <DropdownMenu>
                <DropdownMenuTrigger className="flex h-full items-center gap-1 rounded-md px-2 outline-none hover:text-foreground">
                    {value ? (
                        <>
                            <span className="text-muted-foreground">
                                {label} is
                            </span>
                            <span className="max-w-48 truncate font-medium">
                                {value}
                            </span>
                        </>
                    ) : (
                        <>
                            <Plus className="size-3" />
                            {label}
                        </>
                    )}
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    className="max-h-72 w-52 overflow-y-auto"
                >
                    {children}
                </DropdownMenuContent>
            </DropdownMenu>
            {value && (
                <button
                    type="button"
                    aria-label={`Clear ${label.toLowerCase()} filter`}
                    onClick={onClear}
                    className="flex h-full items-center border-l border-primary/20 px-1.5 text-muted-foreground hover:text-foreground"
                >
                    <X className="size-3" />
                </button>
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
