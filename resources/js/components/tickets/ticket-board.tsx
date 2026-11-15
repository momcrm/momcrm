import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { DragEvent } from 'react';
import StatusPill from '@/components/status-pill';
import QuickAddTicket from '@/components/tickets/quick-add-ticket';
import TicketCard from '@/components/tickets/ticket-card';
import { useTicketStatuses } from '@/components/tickets/ticket-meta';
import { useTicketActions } from '@/hooks/use-ticket-actions';

import { cn } from '@/lib/utils';
import type { TicketListItem, TicketPickerOptions } from '@/types';

/**
 * A column per team status, in the team's order. Dragging a card to another column or place changes its
 * status and order straight away, then saves; a failed save reloads the truth.
 */
export default function TicketBoard({
    tickets,
    favoriteIds,
    options,
    projectId = null,
}: {
    tickets: TicketListItem[];
    favoriteIds: string[];
    options: TicketPickerOptions;
    /** On one project's board, new tickets go in that project. */
    projectId?: string | null;
}) {
    const [addingTo, setAddingTo] = useState<string | null>(null);
    const { openTicket, moveTicket } = useTicketActions();
    const [items, setItems] = useState(tickets);
    const statuses = useTicketStatuses(
        ...items.map((ticket) => ticket.status_id),
    );
    const [draggingId, setDraggingId] = useState<string | null>(null);
    const [target, setTarget] = useState<{
        statusId: string;
        beforeId: string | null;
    } | null>(null);

    useEffect(() => setItems(tickets), [tickets]);

    const columns = statuses.map((status) => ({
        ...status,
        tickets: items.filter((ticket) => ticket.status_id === status.id),
    }));

    const drop = (statusId: string, beforeId: string | null) => {
        const dragged = items.find((ticket) => ticket.id === draggingId);

        setDraggingId(null);
        setTarget(null);

        if (
            !dragged ||
            dragged.id === beforeId ||
            !acceptsDrop(statusId, dragged.status_id)
        ) {
            return;
        }

        const column = items.filter(
            (ticket) =>
                ticket.status_id === statusId && ticket.id !== dragged.id,
        );
        const index = beforeId
            ? Math.max(
                  0,
                  column.findIndex((ticket) => ticket.id === beforeId),
              )
            : column.length;
        const moved = { ...dragged, status_id: statusId };

        column.splice(index, 0, moved);
        setItems([
            ...items.filter(
                (ticket) =>
                    ticket.status_id !== statusId && ticket.id !== dragged.id,
            ),
            ...column,
        ]);
        moveTicket(dragged.id, statusId, index);
    };

    /** An archived status takes no new tickets, only reordering its own. */
    const acceptsDrop = (statusId: string, fromStatusId: string | null) =>
        statusId === fromStatusId ||
        !statuses.find((status) => status.id === statusId)?.is_archived;

    const allowDrop =
        (statusId: string, beforeId: string | null) => (event: DragEvent) => {
            const dragged = items.find((ticket) => ticket.id === draggingId);

            if (!dragged || !acceptsDrop(statusId, dragged.status_id)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (target?.statusId !== statusId || target.beforeId !== beforeId) {
                setTarget({ statusId, beforeId });
            }
        };

    return (
        <div className="-mx-4 flex min-h-[60vh] gap-3 overflow-x-auto px-4 pb-4 md:-mx-6 md:px-6">
            {columns.map((column) => (
                <section
                    key={column.id}
                    aria-label={column.name}
                    onDragOver={allowDrop(column.id, null)}
                    onDrop={(event) => {
                        event.preventDefault();
                        drop(column.id, target?.beforeId ?? null);
                    }}
                    className={cn(
                        'flex w-72 shrink-0 flex-col rounded-xl p-1',
                        target?.statusId === column.id && 'ring-2 ring-ring/40',
                    )}
                >
                    <header className="flex items-center gap-2 px-1.5 pt-0.5 pb-2">
                        <StatusPill hue={column.hue}>{column.name}</StatusPill>
                        <span className="text-xs text-subtle-foreground">
                            {column.tickets.length}
                        </span>
                        {column.is_archived ? (
                            <span className="ml-auto text-xs text-subtle-foreground">
                                Archived
                            </span>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setAddingTo(column.id)}
                                aria-label={`Add a ticket to ${column.name}`}
                                className="ml-auto rounded p-0.5 text-subtle-foreground hover:bg-accent hover:text-foreground"
                            >
                                <Plus className="size-4" />
                            </button>
                        )}
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
                                onDragOver={allowDrop(column.id, ticket.id)}
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
                    {column.is_archived ? null : addingTo === column.id ? (
                        <QuickAddTicket
                            statusId={column.id}
                            options={options}
                            projectId={projectId}
                            onClose={() => setAddingTo(null)}
                        />
                    ) : (
                        <button
                            type="button"
                            onClick={() => setAddingTo(column.id)}
                            className="mt-1 flex items-center gap-1.5 rounded-md px-1.5 py-1 text-left text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            <Plus className="size-3.5" />
                            Add ticket
                        </button>
                    )}
                </section>
            ))}
        </div>
    );
}
