import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { DragEvent } from 'react';
import StatusPill from '@/components/status-pill';
import TicketCard from '@/components/tickets/ticket-card';
import { ticketStatuses } from '@/components/tickets/ticket-meta';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { cn } from '@/lib/utils';
import { create } from '@/routes/tickets';
import type { TicketListItem, TicketStatus } from '@/types';

/**
 * A column per status. Dragging a card to another column or place changes its
 * status and order straight away, then saves; a failed save reloads the truth.
 */
export default function TicketBoard({
    tickets,
    favoriteIds,
}: {
    tickets: TicketListItem[];
    favoriteIds: string[];
}) {
    const { openTicket, moveTicket } = useTicketActions();
    const [items, setItems] = useState(tickets);
    const [draggingId, setDraggingId] = useState<string | null>(null);
    const [target, setTarget] = useState<{
        status: TicketStatus;
        beforeId: string | null;
    } | null>(null);

    useEffect(() => setItems(tickets), [tickets]);

    const columns = ticketStatuses.map((status) => ({
        ...status,
        tickets: items.filter((ticket) => ticket.status === status.value),
    }));

    const drop = (status: TicketStatus, beforeId: string | null) => {
        const dragged = items.find((ticket) => ticket.id === draggingId);

        setDraggingId(null);
        setTarget(null);

        if (!dragged || dragged.id === beforeId) {
            return;
        }

        const column = items.filter(
            (ticket) => ticket.status === status && ticket.id !== dragged.id,
        );
        const index = beforeId
            ? Math.max(
                  0,
                  column.findIndex((ticket) => ticket.id === beforeId),
              )
            : column.length;
        const moved = { ...dragged, status };

        column.splice(index, 0, moved);
        setItems([
            ...items.filter(
                (ticket) =>
                    ticket.status !== status && ticket.id !== dragged.id,
            ),
            ...column,
        ]);
        moveTicket(dragged.id, status, index);
    };

    const allowDrop =
        (status: TicketStatus, beforeId: string | null) =>
        (event: DragEvent) => {
            if (!draggingId) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (target?.status !== status || target.beforeId !== beforeId) {
                setTarget({ status, beforeId });
            }
        };

    return (
        <div className="-mx-4 flex min-h-[60vh] gap-3 overflow-x-auto px-4 pb-4 md:-mx-6 md:px-6">
            {columns.map((column) => (
                <section
                    key={column.value}
                    aria-label={column.label}
                    onDragOver={allowDrop(column.value, null)}
                    onDrop={(event) => {
                        event.preventDefault();
                        drop(column.value, target?.beforeId ?? null);
                    }}
                    className={cn(
                        'flex w-72 shrink-0 flex-col rounded-xl p-1',
                        target?.status === column.value &&
                            'ring-2 ring-ring/40',
                    )}
                >
                    <header className="flex items-center gap-2 px-1.5 pt-0.5 pb-2">
                        <StatusPill tone={column.tone}>
                            {column.label}
                        </StatusPill>
                        <span className="text-xs text-subtle-foreground">
                            {column.tickets.length}
                        </span>
                        <Link
                            href={create()}
                            aria-label={`New ticket in ${column.label}`}
                            className="ml-auto rounded p-0.5 text-subtle-foreground hover:bg-accent hover:text-foreground"
                        >
                            <Plus className="size-4" />
                        </Link>
                    </header>
                    <ol className="flex flex-col gap-2">
                        {column.tickets.map((ticket) => (
                            <li
                                key={ticket.id}
                                draggable
                                onDragStart={(event) => {
                                    event.dataTransfer.effectAllowed = 'move';
                                    setDraggingId(ticket.id);
                                }}
                                onDragEnd={() => {
                                    setDraggingId(null);
                                    setTarget(null);
                                }}
                                onDragOver={allowDrop(column.value, ticket.id)}
                                className={cn(
                                    target?.beforeId === ticket.id &&
                                        'border-t-2 border-primary pt-1',
                                )}
                            >
                                <TicketCard
                                    ticket={ticket}
                                    isFavorite={favoriteIds.includes(ticket.id)}
                                    isDragging={draggingId === ticket.id}
                                    onOpen={() => openTicket(ticket.id)}
                                />
                            </li>
                        ))}
                    </ol>
                    {column.tickets.length === 0 && (
                        <p className="rounded-lg border border-dashed px-2 py-6 text-center text-xs text-subtle-foreground">
                            Drop tickets here
                        </p>
                    )}
                    <Link
                        href={create()}
                        className="mt-1 flex items-center gap-1.5 rounded-md px-1.5 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                    >
                        <Plus className="size-3.5" />
                        Add ticket
                    </Link>
                </section>
            ))}
        </div>
    );
}
