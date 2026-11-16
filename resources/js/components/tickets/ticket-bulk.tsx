import { router } from '@inertiajs/react';
import { Archive, CircleDot, UserRound, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { toast } from 'sonner';
import {
    AssigneeAvatar,
    PriorityGlyph,
    StatusGlyph,
    useTicketPriorities,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { archive, update } from '@/routes/tickets/bulk';
import type { TicketPickerOptions } from '@/types';

export type TicketSelection = {
    ids: string[];
    isSelected: (id: string) => boolean;
    toggle: (id: string) => void;
    /** Picks or drops several tickets together, such as a whole view. */
    setMany: (ids: string[], isOn: boolean) => void;
    clear: () => void;
};

/**
 * The tickets picked in a view for a bulk change. Tickets that leave the
 * view (filtered out, archived) drop out of the selection too.
 */
export function useTicketSelection(visibleIds: string[]): TicketSelection {
    const [ids, setIds] = useState<string[]>([]);
    const visibleKey = visibleIds.join(',');

    useEffect(() => {
        setIds((current) => {
            const kept = current.filter((id) => visibleIds.includes(id));

            return kept.length === current.length ? current : kept;
        });
    }, [visibleKey]); // eslint-disable-line react-hooks/exhaustive-deps

    const clear = useCallback(() => setIds([]), []);

    return useMemo(
        () => ({
            ids,
            isSelected: (id) => ids.includes(id),
            toggle: (id) =>
                setIds((current) =>
                    current.includes(id)
                        ? current.filter((item) => item !== id)
                        : [...current, id],
                ),
            setMany: (many, isOn) =>
                setIds((current) =>
                    isOn
                        ? [...new Set([...current, ...many])]
                        : current.filter((id) => !many.includes(id)),
                ),
            clear,
        }),
        [ids, clear],
    );
}

/**
 * A small checkbox for picking rows, as in gr8r; `indeterminate` shows a
 * dash when only some of a group are picked.
 */
export function SelectCheckbox({
    checked,
    indeterminate = false,
    onChange,
    label,
    className,
}: {
    checked: boolean;
    indeterminate?: boolean;
    onChange: (checked: boolean) => void;
    label: string;
    className?: string;
}) {
    const ref = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (ref.current) {
            ref.current.indeterminate = indeterminate && !checked;
        }
    }, [indeterminate, checked]);

    const isMixed = indeterminate && !checked;

    return (
        <span
            className={cn(
                'relative inline-grid size-[15px] shrink-0 place-items-center',
                className,
            )}
        >
            <input
                ref={ref}
                type="checkbox"
                aria-label={label}
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                onClick={(event) => event.stopPropagation()}
                className="peer col-start-1 row-start-1 size-[15px] cursor-pointer appearance-none rounded-[4px] border-[1.5px] border-border-strong bg-card transition-colors outline-none checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary hover:border-subtle-foreground focus-visible:ring-2 focus-visible:ring-ring"
            />
            <svg
                viewBox="0 0 15 15"
                aria-hidden
                className={cn(
                    'pointer-events-none col-start-1 row-start-1 size-[15px] text-primary-foreground',
                    !checked && !isMixed && 'hidden',
                )}
            >
                {isMixed ? (
                    <path
                        d="M4.25 7.5h6.5"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                    />
                ) : (
                    <path
                        d="M4.1 7.7 6.3 9.9 10.9 5.2"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                )}
            </svg>
        </span>
    );
}

const RELOAD = ['tickets', 'drawer', 'favoriteTicketIds', 'sidebarCounts'];

/**
 * The dark bar that floats at the foot of a ticket view once tickets are
 * picked: change their status, assignee or priority, or archive them.
 */
export function TicketBulkBar({
    selection,
    options,
}: {
    selection: TicketSelection;
    options: TicketPickerOptions;
}) {
    const statuses = useTicketStatuses().filter(
        (status) => !status.is_archived,
    );
    const priorities = useTicketPriorities().filter(
        (priority) => !priority.is_archived,
    );
    const [isSaving, setIsSaving] = useState(false);
    const count = selection.ids.length;

    useEffect(() => {
        if (count === 0) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (
                event.key === 'Escape' &&
                !document.querySelector('[role="menu"]')
            ) {
                selection.clear();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [count, selection]);

    if (count === 0) {
        return null;
    }

    const visitOptions = {
        only: RELOAD,
        preserveScroll: true,
        preserveState: true,
        onStart: () => setIsSaving(true),
        onFinish: () => setIsSaving(false),
        onSuccess: () => selection.clear(),
        onError: (errors: Record<string, string>) => {
            const message = Object.values(errors)[0];

            if (message) {
                toast.error(message);
            }
        },
    };

    const change = (fields: Record<string, string | null>) =>
        router.patch(
            update.url(),
            { ticket_ids: selection.ids, ...fields },
            visitOptions,
        );

    return (
        <div
            role="toolbar"
            aria-label="Bulk actions"
            className="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 animate-in items-center gap-1 rounded-[10px] bg-foreground py-1.5 pr-1.5 pl-3.5 text-[12.5px] text-background shadow-pop duration-300 fade-in slide-in-from-bottom-2"
        >
            <span className="font-semibold whitespace-nowrap tabular-nums">
                {count} selected
            </span>
            <span className="mx-1 h-[18px] w-px bg-background/20" />

            <BarMenu
                icon={<CircleDot className="size-3.5" />}
                label="Status"
                disabled={isSaving}
            >
                {statuses.map((status) => (
                    <DropdownMenuItem
                        key={status.id}
                        onSelect={() => change({ status_id: status.id })}
                    >
                        <StatusGlyph status={status} />
                        {status.name}
                    </DropdownMenuItem>
                ))}
            </BarMenu>

            <BarMenu
                icon={<UserRound className="size-3.5" />}
                label="Assignee"
                disabled={isSaving}
            >
                <DropdownMenuItem
                    onSelect={() => change({ assignee_id: null })}
                >
                    <AssigneeAvatar name={null} />
                    Unassigned
                </DropdownMenuItem>
                {options.members.length > 0 && <DropdownMenuSeparator />}
                {options.members.map((member) => (
                    <DropdownMenuItem
                        key={member.id}
                        onSelect={() => change({ assignee_id: member.id })}
                    >
                        <AssigneeAvatar name={member.name} />
                        {member.name}
                    </DropdownMenuItem>
                ))}
            </BarMenu>

            <BarMenu
                icon={
                    <PriorityGlyph
                        priority={undefined}
                        className="text-current"
                    />
                }
                label="Priority"
                disabled={isSaving}
            >
                {priorities.map((priority) => (
                    <DropdownMenuItem
                        key={priority.id}
                        onSelect={() => change({ priority_id: priority.id })}
                    >
                        <PriorityGlyph priority={priority} />
                        {priority.name}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    onSelect={() => change({ priority_id: null })}
                >
                    <PriorityGlyph priority={undefined} />
                    No priority
                </DropdownMenuItem>
            </BarMenu>

            <BarButton
                disabled={isSaving}
                onClick={() =>
                    router.post(
                        archive.url(),
                        { ticket_ids: selection.ids },
                        visitOptions,
                    )
                }
            >
                <Archive className="size-3.5" />
                Archive
            </BarButton>

            <button
                type="button"
                aria-label="Clear selection"
                onClick={selection.clear}
                className="ml-0.5 flex size-[26px] items-center justify-center rounded-md opacity-85 transition hover:bg-background/15 hover:opacity-100"
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}

function BarButton({
    children,
    className,
    ...props
}: ComponentProps<'button'>) {
    return (
        <button
            type="button"
            className={cn(
                'inline-flex h-[26px] items-center gap-1.5 rounded-md px-2 font-medium whitespace-nowrap opacity-85 transition outline-none hover:bg-background/15 hover:opacity-100 disabled:pointer-events-none disabled:opacity-50 data-[state=open]:bg-background/15 data-[state=open]:opacity-100',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}

function BarMenu({
    icon,
    label,
    disabled,
    children,
}: {
    icon: ReactNode;
    label: string;
    disabled: boolean;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild disabled={disabled}>
                <BarButton>
                    {icon}
                    <span className="hidden sm:inline">{label}</span>
                </BarButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                side="top"
                align="start"
                sideOffset={8}
                className="max-h-72 w-52 overflow-y-auto"
            >
                {children}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
