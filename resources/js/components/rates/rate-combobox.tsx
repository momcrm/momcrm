import { Check, ChevronDown, ChevronRight, Search } from 'lucide-react';
import {
    useCallback,
    useEffect,
    useId,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';
import type { Rate } from '@/types';

const PANEL_MIN_WIDTH = 320;
const PANEL_MAX_HEIGHT = 320;
const PANEL_GAP = 4;
const INDENT_PX = 16;

type Row = {
    id: string;
    rate: Rate | null;
    depth: number;
    /** Ancestors left out of the list, shown before the name. */
    ancestry: string;
    parentId: string | null;
    hasChildren: boolean;
};

type Position = {
    top: number;
    left: number;
    width: number;
    maxHeight: number;
};

/**
 * The rates as rows of a tree: each rate under the nearest of its parents
 * that is in the list. A rate whose parent was left out (one not priced in
 * the customer's currency, say) sits at the top and shows that parent's path.
 */
function treeRows(rates: Rate[]): Row[] {
    const byId = new Map(rates.map((rate) => [rate.id, rate]));
    const parentOf = (rate: Rate): string | null =>
        rate.parent_id !== null && byId.has(rate.parent_id)
            ? rate.parent_id
            : null;
    const withChildren = new Set(
        rates.map(parentOf).filter((id): id is string => id !== null),
    );
    const depths = new Map<string, number>();

    return rates.map((rate) => {
        const parentId = parentOf(rate);
        const depth = parentId === null ? 0 : (depths.get(parentId) ?? 0) + 1;

        depths.set(rate.id, depth);

        return {
            id: rate.id,
            rate,
            depth,
            ancestry:
                parentId === null && rate.path !== rate.name
                    ? rate.path.slice(0, rate.path.length - rate.name.length)
                    : '',
            parentId,
            hasChildren: withChildren.has(rate.id),
        };
    });
}

/**
 * The element the panel is drawn in. Inside a dialog it must stay within the
 * dialog, which traps focus and clicks; elsewhere it goes on the body, so a
 * card that clips its overflow can't cut it off.
 */
function panelHost(trigger: HTMLElement): HTMLElement {
    return (
        trigger.closest<HTMLElement>('[role="dialog"]') ??
        trigger.ownerDocument.body
    );
}

/**
 * Picks a rate from the price list, shown as a tree. Type to search by name
 * or path. It submits with the form through a hidden input named `name`.
 */
export default function RateCombobox({
    id,
    name,
    rates,
    value,
    defaultValue = '',
    onValueChange,
    placeholder = 'Choose a rate',
    emptyLabel,
    detail,
    invalid = false,
    disabled = false,
    className,
    'aria-label': ariaLabel,
}: {
    id?: string;
    /** Submits the chosen rate's id under this name. */
    name?: string;
    /** The rates to choose from, in tree order. */
    rates: Rate[];
    value?: string;
    defaultValue?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    /** Offers choosing no rate, labelled with this. */
    emptyLabel?: string;
    /** What to show beside a rate, such as its price. */
    detail?: (rate: Rate) => ReactNode;
    invalid?: boolean;
    disabled?: boolean;
    className?: string;
    'aria-label'?: string;
}) {
    const [ownValue, setOwnValue] = useState(defaultValue);
    const selected = value ?? ownValue;
    const [isOpen, setIsOpen] = useState(false);
    const [term, setTerm] = useState('');
    const [collapsed, setCollapsed] = useState<Set<string>>(new Set());
    const [activeId, setActiveId] = useState<string | null>(null);
    const [position, setPosition] = useState<Position | null>(null);
    const [host, setHost] = useState<HTMLElement | null>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const panelRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLDivElement>(null);
    const baseId = useId();
    const treeId = `${baseId}-tree`;
    const optionId = (rowId: string) => `${baseId}-option-${rowId || 'none'}`;

    const rows = useMemo(() => treeRows(rates), [rates]);
    const selectedRate = rates.find((rate) => rate.id === selected) ?? null;
    const query = term.trim().toLowerCase();

    const visible = useMemo<Row[]>(() => {
        const byId = new Map(rows.map((row) => [row.id, row]));
        const empty: Row[] =
            emptyLabel !== undefined && !query
                ? [
                      {
                          id: '',
                          rate: null,
                          depth: 0,
                          ancestry: '',
                          parentId: null,
                          hasChildren: false,
                      },
                  ]
                : [];

        if (query) {
            const shown = new Set<string>();

            rows.forEach((row) => {
                if (!row.rate?.path.toLowerCase().includes(query)) {
                    return;
                }

                let current: Row | undefined = row;

                while (current && !shown.has(current.id)) {
                    shown.add(current.id);
                    current = current.parentId
                        ? byId.get(current.parentId)
                        : undefined;
                }
            });

            return rows.filter((row) => shown.has(row.id));
        }

        const hidden = new Set<string>();

        return [
            ...empty,
            ...rows.filter((row) => {
                const isHidden =
                    row.parentId !== null &&
                    (hidden.has(row.parentId) || collapsed.has(row.parentId));

                if (isHidden) {
                    hidden.add(row.id);
                }

                return !isHidden;
            }),
        ];
    }, [rows, query, collapsed, emptyLabel]);

    const matches = (row: Row) =>
        !query || Boolean(row.rate?.path.toLowerCase().includes(query));

    const place = useCallback(() => {
        const trigger = triggerRef.current;

        if (!trigger) {
            return;
        }

        const target = panelHost(trigger);
        const rect = trigger.getBoundingClientRect();
        const origin =
            target === trigger.ownerDocument.body
                ? { top: 0, left: 0 }
                : target.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom - PANEL_GAP * 2;
        const above = rect.top - PANEL_GAP * 2;
        const opensUp = below < 200 && above > below;
        const maxHeight = Math.min(PANEL_MAX_HEIGHT, opensUp ? above : below);
        const width = Math.max(rect.width, PANEL_MIN_WIDTH);
        const left = Math.min(rect.left, window.innerWidth - width - 8);

        setHost(target);
        setPosition({
            top: opensUp
                ? rect.top - PANEL_GAP - maxHeight - origin.top
                : rect.bottom + PANEL_GAP - origin.top,
            left: Math.max(8, left) - origin.left,
            width,
            maxHeight,
        });
    }, []);

    const open = (seed = '') => {
        if (disabled) {
            return;
        }

        const ancestors = new Set<string>();
        let parentId = selectedRate?.parent_id ?? null;

        while (parentId && !ancestors.has(parentId)) {
            ancestors.add(parentId);
            parentId =
                rates.find((rate) => rate.id === parentId)?.parent_id ?? null;
        }

        setCollapsed(
            (current) =>
                new Set([...current].filter((id) => !ancestors.has(id))),
        );
        setTerm(seed);
        setActiveId(
            selectedRate
                ? selected
                : emptyLabel !== undefined
                  ? ''
                  : (rows[0]?.id ?? null),
        );
        place();
        setIsOpen(true);
    };

    const close = useCallback((refocus = true) => {
        setIsOpen(false);
        setTerm('');

        if (refocus) {
            triggerRef.current?.focus();
        }
    }, []);

    const choose = (row: Row) => {
        setOwnValue(row.id);
        onValueChange?.(row.id);
        close();
    };

    useLayoutEffect(() => {
        if (!isOpen) {
            return;
        }

        const onScroll = (event: Event) => {
            if (!panelRef.current?.contains(event.target as Node)) {
                place();
            }
        };

        window.addEventListener('resize', place);
        window.addEventListener('scroll', onScroll, true);

        return () => {
            window.removeEventListener('resize', place);
            window.removeEventListener('scroll', onScroll, true);
        };
    }, [isOpen, place]);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        /**
         * Escape closes the panel before a dialog around it sees the key: the
         * window hears it ahead of the document, where the dialog listens.
         */
        const onEscape = (event: globalThis.KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.stopPropagation();
                close();
            }
        };
        const onPointerDown = (event: PointerEvent) => {
            const target = event.target as Node;

            if (
                !panelRef.current?.contains(target) &&
                !triggerRef.current?.contains(target)
            ) {
                close(false);
            }
        };

        window.addEventListener('keydown', onEscape, true);
        document.addEventListener('pointerdown', onPointerDown);

        return () => {
            window.removeEventListener('keydown', onEscape, true);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, [isOpen, close]);

    useEffect(() => {
        if (activeId === null) {
            return;
        }

        listRef.current
            ?.querySelector(`[id="${CSS.escape(optionId(activeId))}"]`)
            ?.scrollIntoView({ block: 'nearest' });
    }, [activeId, isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    const toggle = (rowId: string, expand?: boolean) =>
        setCollapsed((current) => {
            const next = new Set(current);
            const isExpanding = expand ?? next.has(rowId);

            if (isExpanding) {
                next.delete(rowId);
            } else {
                next.add(rowId);
            }

            return next;
        });

    const onSearchKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        const index = visible.findIndex((row) => row.id === activeId);
        const active = index >= 0 ? visible[index] : null;
        const move = (step: number) => {
            event.preventDefault();

            if (visible.length === 0) {
                return;
            }

            const next =
                index < 0
                    ? step > 0
                        ? 0
                        : visible.length - 1
                    : Math.min(visible.length - 1, Math.max(0, index + step));

            setActiveId(visible[next].id);
        };

        switch (event.key) {
            case 'ArrowDown':
                move(1);
                break;
            case 'ArrowUp':
                move(-1);
                break;
            case 'Home':
                if (!term) {
                    move(-visible.length);
                }

                break;
            case 'End':
                if (!term) {
                    move(visible.length);
                }

                break;
            case 'ArrowRight':
                if (!term && active?.hasChildren) {
                    event.preventDefault();

                    if (collapsed.has(active.id)) {
                        toggle(active.id, true);
                    } else {
                        move(1);
                    }
                }

                break;
            case 'ArrowLeft':
                if (!term && active) {
                    event.preventDefault();

                    if (active.hasChildren && !collapsed.has(active.id)) {
                        toggle(active.id, false);
                    } else if (active.parentId) {
                        setActiveId(active.parentId);
                    }
                }

                break;
            case 'Enter':
                event.preventDefault();

                if (active && matches(active)) {
                    choose(active);
                }

                break;
            case 'Tab':
                close(false);
                break;
        }
    };

    const onTriggerKeyDown = (event: KeyboardEvent<HTMLButtonElement>) => {
        if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            open();
        } else if (
            event.key.length === 1 &&
            event.key !== ' ' &&
            !event.metaKey &&
            !event.ctrlKey &&
            !event.altKey
        ) {
            event.preventDefault();
            open(event.key);
        }
    };

    const panel = isOpen && position && host && (
        <div
            ref={panelRef}
            style={{
                top: position.top,
                left: position.left,
                width: position.width,
                maxHeight: position.maxHeight,
            }}
            className="fixed z-50 flex flex-col overflow-hidden rounded-xl bg-popover text-popover-foreground shadow-pop"
        >
            <div className="flex items-center gap-2 border-b px-2.5">
                <Search className="size-3.5 shrink-0 text-muted-foreground" />
                <input
                    autoFocus
                    role="combobox"
                    aria-label="Search rates"
                    aria-expanded
                    aria-controls={treeId}
                    aria-autocomplete="list"
                    aria-activedescendant={
                        activeId !== null ? optionId(activeId) : undefined
                    }
                    value={term}
                    onChange={(event) => {
                        const next = event.target.value;
                        const nextQuery = next.trim().toLowerCase();

                        setTerm(next);
                        setActiveId(
                            rows.find((row) =>
                                row.rate?.path
                                    .toLowerCase()
                                    .includes(nextQuery),
                            )?.id ?? null,
                        );
                    }}
                    onKeyDown={onSearchKeyDown}
                    placeholder="Search rates"
                    className="h-9 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                />
            </div>
            <div
                ref={listRef}
                id={treeId}
                role="tree"
                aria-label="Rates"
                className="min-h-0 flex-1 overflow-y-auto p-1"
            >
                {visible.length === 0 && (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                        {rates.length === 0
                            ? 'No rates to choose from.'
                            : `No rates match “${term.trim()}”.`}
                    </p>
                )}
                {visible.map((row) => {
                    const isMatch = matches(row);
                    const isExpanded =
                        row.hasChildren &&
                        (query !== '' || !collapsed.has(row.id));

                    return (
                        <div
                            key={row.id || 'none'}
                            id={optionId(row.id)}
                            role="treeitem"
                            aria-level={row.depth + 1}
                            aria-selected={row.id === selected}
                            aria-expanded={
                                row.hasChildren ? isExpanded : undefined
                            }
                            aria-disabled={!isMatch || undefined}
                            onPointerMove={() => setActiveId(row.id)}
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => isMatch && choose(row)}
                            style={{ paddingLeft: 4 + row.depth * INDENT_PX }}
                            className={cn(
                                'flex min-h-8 cursor-pointer items-center gap-1 rounded-md pr-2 text-sm',
                                row.id === activeId &&
                                    'bg-accent text-accent-foreground',
                                !isMatch &&
                                    'cursor-default text-muted-foreground',
                            )}
                        >
                            {row.hasChildren ? (
                                <button
                                    type="button"
                                    tabIndex={-1}
                                    aria-label={
                                        isExpanded ? 'Collapse' : 'Expand'
                                    }
                                    disabled={query !== ''}
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        toggle(row.id);
                                    }}
                                    className="flex size-5 shrink-0 items-center justify-center rounded-sm text-muted-foreground hover:bg-muted hover:text-foreground disabled:pointer-events-none"
                                >
                                    {isExpanded ? (
                                        <ChevronDown className="size-3.5" />
                                    ) : (
                                        <ChevronRight className="size-3.5" />
                                    )}
                                </button>
                            ) : (
                                <span className="size-5 shrink-0" />
                            )}
                            <span className="min-w-0 flex-1 truncate py-1">
                                {row.rate === null ? (
                                    <span className="text-muted-foreground">
                                        {emptyLabel}
                                    </span>
                                ) : (
                                    <>
                                        {row.ancestry && (
                                            <span className="text-muted-foreground">
                                                {row.ancestry}
                                            </span>
                                        )}
                                        {row.rate.name}
                                        {row.rate.is_archived && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                (archived)
                                            </span>
                                        )}
                                    </>
                                )}
                            </span>
                            {row.rate && detail && (
                                <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                    {detail(row.rate)}
                                </span>
                            )}
                            <Check
                                className={cn(
                                    'size-3.5 shrink-0',
                                    row.id !== selected && 'invisible',
                                )}
                            />
                        </div>
                    );
                })}
            </div>
        </div>
    );

    return (
        <>
            {name !== undefined && (
                <input type="hidden" name={name} value={selected} />
            )}
            <button
                ref={triggerRef}
                id={id}
                type="button"
                disabled={disabled}
                aria-haspopup="tree"
                aria-expanded={isOpen}
                aria-invalid={invalid || undefined}
                aria-label={ariaLabel}
                onClick={() => (isOpen ? close() : open())}
                onKeyDown={onTriggerKeyDown}
                className={cn(
                    'flex h-control-md w-full min-w-0 cursor-pointer items-center gap-2 rounded-md border border-border-strong bg-card pr-2 pl-2.5 text-left text-sm transition-[color,border-color,box-shadow] duration-150 outline-none hover:border-[color-mix(in_srgb,var(--border-strong)_60%,var(--muted-foreground))] focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive aria-invalid:ring-[3px] aria-invalid:ring-destructive/10',
                    className,
                )}
            >
                <span className="min-w-0 flex-1 truncate">
                    {selectedRate ? (
                        <>
                            {selectedRate.path !== selectedRate.name && (
                                <span className="text-muted-foreground">
                                    {selectedRate.path.slice(
                                        0,
                                        selectedRate.path.length -
                                            selectedRate.name.length,
                                    )}
                                </span>
                            )}
                            {selectedRate.name}
                        </>
                    ) : (
                        <span className="text-muted-foreground">
                            {emptyLabel ?? placeholder}
                        </span>
                    )}
                </span>
                {selectedRate && detail && (
                    <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                        {detail(selectedRate)}
                    </span>
                )}
                <ChevronDown className="size-3.5 shrink-0 text-muted-foreground" />
            </button>
            {panel && createPortal(panel, host)}
        </>
    );
}
