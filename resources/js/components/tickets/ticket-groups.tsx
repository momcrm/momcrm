import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import StatusPill from '@/components/status-pill';
import TicketRow from '@/components/tickets/ticket-row';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { cn } from '@/lib/utils';
import type { Tone, TicketListItem } from '@/types';

export type TicketGroup = {
    key: string;
    label: string;
    tone?: Tone;
    /** A colour class for the group's dot, such as a team status's colour. */
    dotClassName?: string;
    tickets: TicketListItem[];
    collapsed?: boolean;
};

/**
 * Tickets in collapsible groups (by status, or by when they are due), each with
 * its count.
 */
export default function TicketGroups({
    groups,
    favoriteIds,
}: {
    groups: TicketGroup[];
    favoriteIds: string[];
}) {
    const { openTicket } = useTicketActions();
    const [collapsed, setCollapsed] = useState<Record<string, boolean>>(() =>
        Object.fromEntries(
            groups.map((group) => [group.key, Boolean(group.collapsed)]),
        ),
    );

    return (
        <div className="overflow-hidden rounded-lg border bg-card shadow-xs">
            {groups
                .filter((group) => group.tickets.length > 0)
                .map((group) => {
                    const isCollapsed = collapsed[group.key];

                    return (
                        <section
                            key={group.key}
                            className="border-b last:border-b-0"
                        >
                            <button
                                type="button"
                                aria-expanded={!isCollapsed}
                                onClick={() =>
                                    setCollapsed({
                                        ...collapsed,
                                        [group.key]: !isCollapsed,
                                    })
                                }
                                className="flex h-9 w-full items-center gap-2 bg-muted/60 px-3 text-left"
                            >
                                <ChevronRight
                                    className={cn(
                                        'size-3.5 text-subtle-foreground transition-transform',
                                        !isCollapsed && 'rotate-90',
                                    )}
                                />
                                <StatusPill
                                    tone={group.tone}
                                    dotClassName={group.dotClassName}
                                >
                                    {group.label}
                                </StatusPill>
                                <span className="text-xs text-subtle-foreground">
                                    {group.tickets.length}
                                </span>
                            </button>
                            {!isCollapsed && (
                                <ul className="divide-y">
                                    {group.tickets.map((ticket) => (
                                        <li key={ticket.id}>
                                            <TicketRow
                                                ticket={ticket}
                                                isFavorite={favoriteIds.includes(
                                                    ticket.id,
                                                )}
                                                onOpen={() =>
                                                    openTicket(ticket.id)
                                                }
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    );
                })}
        </div>
    );
}
