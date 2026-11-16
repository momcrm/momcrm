import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { DragEvent } from 'react';
import QuickAddTicket from '@/components/tickets/quick-add-ticket';
import TicketCard from '@/components/tickets/ticket-card';
import {
    StatusGlyph,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
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
        <div className="flex min-h-[60vh] items-start gap-3 overflow-x-auto px-4 pt-3.5 pb-6 after:flex-[0_0_8px] after:content-[''] md:px-6">
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
                        'group/col flex w-[284px] shrink-0 flex-col rounded-[10px] border border-divider bg-sunken',
                        target?.statusId === column.id &&
                            'border-primary/40 bg-[color-mix(in_srgb,var(--primary)_6%,var(--sunken))]',
                    )}
                >
                    <header className="flex items-center gap-[7px] pt-2.5 pr-2 pb-1.5 pl-3 text-[12.5px] font-semibold">
                        <StatusGlyph status={column} />
                        {column.name}
                        <span className="font-medium text-muted-foreground">
                            {column.tickets.length}
                        </span>
                        {column.is_archived ? (
                            <span className="ml-auto text-xs font-normal text-subtle-foreground">
                                Archived
                            </span>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setAddingTo(column.id)}
                                aria-label={`Add a ticket to ${column.name}`}
                                className="ml-auto flex size-[22px] items-center justify-center rounded-md text-soft-foreground opacity-55 transition-opacity group-hover/col:opacity-100 hover:bg-accent hover:text-foreground"
                            >
                                <Plus className="size-3.5" />
                            </button>
                        )}
                    </header>
                    <ol className="flex min-h-[60px] flex-col gap-1.5 px-2 pt-1 pb-2">
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
                                        'relative before:absolute before:inset-x-0.5 before:-top-1 before:h-0.5 before:rounded-full before:bg-primary',
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
                    {column.is_archived ? null : addingTo === column.id ? (
                        <div className="px-2 pb-2">
                            <QuickAddTicket
                                statusId={column.id}
                                options={options}
                                projectId={projectId}
                                onClose={() => setAddingTo(null)}
                            />
                        </div>
                    ) : (
                        <div className="px-2 pb-2">
                            <button
                                type="button"
                                onClick={() => setAddingTo(column.id)}
                                className="flex h-[30px] w-full items-center gap-1.5 rounded-md px-2 text-left text-[12.5px] text-muted-foreground hover:bg-accent hover:text-foreground"
                            >
                                <Plus className="size-3.5" />
                                Add ticket
                            </button>
                        </div>
                    )}
                </section>
            ))}
        </div>
    );
}
