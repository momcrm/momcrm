import { router, useHttp } from '@inertiajs/react';
import {
    Box,
    Building2,
    CircleDashed,
    FolderKanban,
    Moon,
    Plus,
    Receipt,
    Search,
    Sun,
    UserRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import { useAppearance } from '@/hooks/use-appearance';
import { useShortcuts } from '@/hooks/use-shortcuts';
import { cn, toUrl } from '@/lib/utils';
import {
    footerNavigation,
    workspaceNavigation,
} from '@/lib/workspace-navigation';
import { search } from '@/routes';
import { create as createCustomer } from '@/routes/customers';
import { index as projectsIndex } from '@/routes/projects';

type SearchHit = {
    id: string;
    title: string;
    subtitle: string | null;
    url: string;
    number?: number;
    status_id?: string | null;
};

type SearchResponse = {
    recentTickets?: SearchHit[];
    tickets?: SearchHit[];
    projects?: SearchHit[];
    customers?: SearchHit[];
    contacts?: SearchHit[];
    records?: SearchHit[];
    invoices?: SearchHit[];
};

type Command = {
    id: string;
    group: string;
    label: string;
    detail?: string | null;
    icon: LucideIcon;
    hint?: ReactNode;
    onSelect: () => void;
};

const SEARCH_DEBOUNCE_MS = 180;

const CommandPaletteContext = createContext<{
    open: () => void;
} | null>(null);

/**
 * Opens the ⌘K palette from anywhere inside the app layout.
 */
export function useCommandPalette(): { open: () => void } {
    const context = useContext(CommandPaletteContext);

    if (!context) {
        throw new Error(
            'useCommandPalette must be used inside CommandPaletteProvider.',
        );
    }

    return context;
}

function Keys({ keys }: { keys: string[] }) {
    return (
        <span className="flex items-center gap-1">
            {keys.map((key) => (
                <kbd
                    key={key}
                    className="flex h-5 min-w-5 items-center justify-center rounded-sm border bg-muted px-1 font-mono text-[10px] text-muted-foreground uppercase"
                >
                    {key}
                </kbd>
            ))}
        </span>
    );
}

/**
 * The ⌘K palette: quick actions, recent tickets, navigation and a search over
 * the team's tickets, customers, contacts and invoices.
 */
export function CommandPaletteProvider({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const open = useCallback(() => setIsOpen(true), []);
    const toggle = useCallback(() => setIsOpen((value) => !value), []);

    useShortcuts({ onTogglePalette: toggle });

    const context = useMemo(() => ({ open }), [open]);

    return (
        <CommandPaletteContext.Provider value={context}>
            {children}
            <Dialog open={isOpen} onOpenChange={setIsOpen}>
                <DialogContent className="top-[12vh] translate-y-0 gap-0 overflow-hidden p-0 shadow-pop sm:max-w-[34rem] [&>button:last-child]:hidden">
                    <DialogTitle className="sr-only">
                        Command palette
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        Search the workspace or run a command.
                    </DialogDescription>
                    {isOpen && <Palette onDone={() => setIsOpen(false)} />}
                </DialogContent>
            </Dialog>
        </CommandPaletteContext.Provider>
    );
}

function Palette({ onDone }: { onDone: () => void }) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<SearchResponse>({});
    const [activeIndex, setActiveIndex] = useState(0);
    const listRef = useRef<HTMLDivElement>(null);
    const http = useHttp<Record<string, never>, SearchResponse>();
    const { openCreateTicket } = useCreateTicket();
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const query = term.trim();

    useEffect(() => {
        const timeout = setTimeout(
            () => {
                http.cancel();
                void http
                    .get(search.url(query ? { query: { q: query } } : {}))
                    .then(setResults)
                    .catch(() => undefined);
            },
            query ? SEARCH_DEBOUNCE_MS : 0,
        );

        return () => clearTimeout(timeout);
    }, [query]); // eslint-disable-line react-hooks/exhaustive-deps

    const go = useCallback(
        (url: string) => {
            onDone();
            router.visit(url);
        },
        [onDone],
    );

    const commands = useMemo<Command[]>(() => {
        const matches = (label: string) =>
            !query || label.toLowerCase().includes(query.toLowerCase());

        const suggestions: Command[] = [
            {
                id: 'create-ticket',
                group: 'Suggestions',
                label: 'Create ticket',
                icon: Plus,
                onSelect: () => {
                    onDone();
                    openCreateTicket();
                },
            },
            {
                id: 'create-project',
                group: 'Suggestions',
                label: 'Create project',
                icon: Plus,
                onSelect: () => go(toUrl(projectsIndex({ query: { new: 1 } }))),
            },
            {
                id: 'create-customer',
                group: 'Suggestions',
                label: 'Create customer',
                icon: Plus,
                onSelect: () => go(toUrl(createCustomer())),
            },
            {
                id: 'toggle-theme',
                group: 'Suggestions',
                label:
                    resolvedAppearance === 'dark'
                        ? 'Switch to light mode'
                        : 'Switch to dark mode',
                icon: resolvedAppearance === 'dark' ? Sun : Moon,
                onSelect: () => {
                    updateAppearance(
                        resolvedAppearance === 'dark' ? 'light' : 'dark',
                    );
                    onDone();
                },
            },
        ].filter((command) => matches(command.label));

        const hits = (
            group: string,
            items: SearchHit[] | undefined,
            icon: LucideIcon,
        ): Command[] =>
            (items ?? []).map((hit) => ({
                id: `${group}-${hit.id}`,
                group,
                label: hit.title,
                detail: hit.subtitle,
                icon,
                hint:
                    hit.number !== undefined ? (
                        <span className="font-mono text-xs text-subtle-foreground">
                            #{hit.number}
                        </span>
                    ) : undefined,
                onSelect: () => go(hit.url),
            }));

        const navigation: Command[] = [
            ...workspaceNavigation().flatMap((section) => section.items),
            ...footerNavigation(),
        ]
            .filter((item) => matches(`Go to ${item.title}`))
            .map((item) => ({
                id: `nav-${item.title}`,
                group: 'Navigation',
                label: `Go to ${item.title}`,
                icon: item.icon ?? Search,
                hint: item.shortcut ? (
                    <Keys keys={['g', item.shortcut]} />
                ) : undefined,
                onSelect: () => go(toUrl(item.href)),
            }));

        return [
            ...(query ? [] : suggestions),
            ...hits('Recent tickets', results.recentTickets, CircleDashed),
            ...hits('Tickets', results.tickets, CircleDashed),
            ...hits('Projects', results.projects, FolderKanban),
            ...hits('Customers', results.customers, Building2),
            ...hits('Contacts', results.contacts, UserRound),
            ...hits('Records', results.records, Box),
            ...hits('Invoices', results.invoices, Receipt),
            ...(query ? suggestions : []),
            ...navigation,
        ];
    }, [query, results, resolvedAppearance, go, onDone, updateAppearance]);

    useEffect(() => setActiveIndex(0), [query, results]);

    useEffect(() => {
        listRef.current
            ?.querySelector(`[data-index="${activeIndex}"]`)
            ?.scrollIntoView({ block: 'nearest' });
    }, [activeIndex]);

    const handleKeyDown = (event: React.KeyboardEvent) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) => Math.min(index + 1, commands.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            commands[activeIndex]?.onSelect();
        }
    };

    let lastGroup: string | null = null;

    return (
        <div onKeyDown={handleKeyDown}>
            <div className="flex h-12 items-center gap-2.5 border-b px-4">
                <Search className="size-4 shrink-0 text-muted-foreground" />
                <input
                    autoFocus
                    value={term}
                    onChange={(event) => setTerm(event.target.value)}
                    placeholder="Type a command or search…"
                    aria-label="Search the workspace"
                    className="h-full flex-1 bg-transparent text-sm outline-none placeholder:text-subtle-foreground"
                />
                <Keys keys={['esc']} />
            </div>

            <div
                ref={listRef}
                role="listbox"
                className="max-h-[min(60vh,26rem)] overflow-y-auto p-1.5"
            >
                {commands.length === 0 && (
                    <p className="px-3 py-8 text-center text-sm text-muted-foreground">
                        {http.processing
                            ? 'Searching…'
                            : `Nothing matches “${query}”.`}
                    </p>
                )}
                {commands.map((command, index) => {
                    const showGroup = command.group !== lastGroup;
                    lastGroup = command.group;

                    return (
                        <div key={command.id}>
                            {showGroup && (
                                <div className="px-2.5 pt-2.5 pb-1 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                    {command.group}
                                </div>
                            )}
                            <button
                                type="button"
                                role="option"
                                aria-selected={index === activeIndex}
                                data-index={index}
                                onMouseMove={() => setActiveIndex(index)}
                                onClick={command.onSelect}
                                className={cn(
                                    'flex h-9 w-full items-center gap-2.5 rounded-md px-2.5 text-left text-sm',
                                    index === activeIndex && 'bg-accent',
                                )}
                            >
                                <command.icon className="size-4 shrink-0 text-muted-foreground" />
                                <span className="truncate">
                                    {command.label}
                                </span>
                                {command.detail && (
                                    <span className="truncate text-xs text-subtle-foreground">
                                        {command.detail}
                                    </span>
                                )}
                                <span className="ml-auto shrink-0">
                                    {command.hint}
                                </span>
                            </button>
                        </div>
                    );
                })}
            </div>

            <div className="flex items-center gap-4 border-t px-4 py-2 text-xs text-subtle-foreground">
                <span className="flex items-center gap-1.5">
                    <Keys keys={['↑', '↓']} /> navigate
                </span>
                <span className="flex items-center gap-1.5">
                    <Keys keys={['↵']} /> select
                </span>
                <span className="ml-auto flex items-center gap-1.5">
                    <Keys keys={['g']} /> then a letter to jump
                </span>
            </div>
        </div>
    );
}
