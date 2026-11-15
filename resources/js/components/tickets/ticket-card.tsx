import { CircleCheck, Star } from 'lucide-react';
import {
    AssigneeAvatar,
    DueChip,
    LabelChips,
    PriorityIcon,
    ProjectTag,
    SubtaskProgress,
    useIsClosedStatus,
} from '@/components/tickets/ticket-meta';
import { cn } from '@/lib/utils';
import type { TicketListItem } from '@/types';

/**
 * A ticket on the board: labels, title, then its number, priority, due date,
 * subtask progress and assignee.
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
    const isClosed = useIsClosedStatus()(ticket.status_id);

    return (
        <button
            type="button"
            onClick={onOpen}
            className={cn(
                'flex w-full flex-col gap-2 rounded-lg border bg-card p-3 text-left shadow-xs transition hover:border-border-strong',
                isDragging && 'opacity-40',
            )}
        >
            <LabelChips labels={ticket.labels} />
            <span
                className={cn(
                    'line-clamp-2 text-sm font-medium',
                    isClosed && 'text-muted-foreground line-through',
                )}
            >
                {isClosed && (
                    <CircleCheck className="mr-1 inline size-3.5 -translate-y-px fill-success text-card" />
                )}
                {isFavorite && (
                    <Star className="mr-1 inline size-3 fill-amber-400 text-amber-400" />
                )}
                {ticket.title}
            </span>
            {(ticket.project_name || ticket.customer_name) && (
                <span className="flex min-w-0 items-center gap-2">
                    <ProjectTag
                        name={ticket.project_name}
                        color={ticket.project_color}
                    />
                    {!ticket.project_name && (
                        <span className="truncate text-xs text-muted-foreground">
                            {ticket.customer_name}
                        </span>
                    )}
                </span>
            )}
            <span className="flex items-center gap-2">
                <span className="font-mono text-[11px] text-subtle-foreground">
                    #{ticket.number}
                </span>
                <PriorityIcon priorityId={ticket.priority_id} />
                <DueChip dueOn={ticket.due_on} isClosed={isClosed} />
                <SubtaskProgress
                    done={ticket.closed_subtasks_count}
                    total={ticket.subtasks_count}
                />
                <AssigneeAvatar
                    name={ticket.assignee_name}
                    className="ml-auto"
                />
            </span>
        </button>
    );
}
