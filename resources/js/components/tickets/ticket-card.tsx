import { ListChecks, Star } from 'lucide-react';
import { LabelChips } from '@/components/labels';
import {
    AssigneeAvatar,
    DueChip,
    PriorityIcon,
    StatusGlyph,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { TicketListItem } from '@/types';

/**
 * A ticket on the board, as in gr8r: labels, title, subtask progress, then
 * its project and number, priority, due date and assignee.
 */
export default function TicketCard({
    ticket,
    isFavorite,
    isDragging,
    onOpen,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    isDragging?: boolean;
    onOpen: () => void;
}) {
    const status = useTicketStatus(ticket.status_id);
    const isClosed = status?.category === 'closed';

    return (
        <button
            type="button"
            onClick={onOpen}
            className={cn(
                'flex w-full flex-col gap-2 rounded-lg bg-card px-3 py-2.5 text-left shadow-card transition-shadow hover:shadow-[0_2px_6px_-2px_rgb(0_0_0/0.08),0_0_0_1px_var(--border-strong)]',
                isDragging && 'opacity-35',
            )}
        >
            {ticket.labels.length > 0 && <LabelChips labels={ticket.labels} />}
            <span className="flex items-start gap-1.5">
                {isClosed && (
                    <StatusGlyph status={status} className="mt-[3px]" />
                )}
                <span
                    className={cn(
                        'min-w-0 flex-1 text-[13.5px] leading-[1.4] font-[450] break-words',
                        isClosed && 'text-muted-foreground line-through',
                    )}
                >
                    {ticket.title}
                    {isFavorite && (
                        <Star className="ml-1 inline size-3 -translate-y-px fill-amber-400 text-amber-400" />
                    )}
                </span>
            </span>
            {ticket.subtasks_count > 0 && (
                <span
                    aria-label={`${ticket.closed_subtasks_count} of ${ticket.subtasks_count} subtasks done`}
                    className="flex items-center gap-2 text-[11px] text-muted-foreground tabular-nums"
                >
                    <span className="inline-flex items-center gap-[3px]">
                        <ListChecks className="size-3" />
                        {ticket.closed_subtasks_count}/{ticket.subtasks_count}
                    </span>
                    <span className="h-1 min-w-10 flex-1 overflow-hidden rounded bg-accent">
                        <span
                            className={cn(
                                'block h-full rounded',
                                ticket.closed_subtasks_count ===
                                    ticket.subtasks_count
                                    ? 'bg-success'
                                    : 'bg-primary',
                            )}
                            style={{
                                width: `${(ticket.closed_subtasks_count / ticket.subtasks_count) * 100}%`,
                            }}
                        />
                    </span>
                </span>
            )}
            <span className="flex min-w-0 items-center gap-2.5 text-xs text-muted-foreground">
                <span className="inline-flex min-w-0 items-center gap-[5px]">
                    {ticket.project_name && (
                        <span
                            aria-label={ticket.project_name}
                            title={ticket.project_name}
                            className="size-2 shrink-0 rounded-[3px] hue-dot"
                            style={hueStyle(ticket.project_hue)}
                        />
                    )}
                    <span className="font-mono text-[11px]">
                        #{ticket.number}
                    </span>
                    {!ticket.project_name && ticket.customer_name && (
                        <span className="truncate">{ticket.customer_name}</span>
                    )}
                </span>
                <PriorityIcon priorityId={ticket.priority_id} />
                <DueChip dueOn={ticket.due_on} isClosed={isClosed} />
                <AssigneeAvatar
                    name={ticket.assignee_name}
                    className="ml-auto"
                />
            </span>
        </button>
    );
}
