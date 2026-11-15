import { Star } from 'lucide-react';
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
 * One line of a ticket list: priority, number, title and labels on the left;
 * project, due date and assignee on the right.
 */
export default function TicketRow({
    ticket,
    isFavorite,
    onOpen,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    onOpen: () => void;
}) {
    const isClosed = useIsClosedStatus()(ticket.status_id);

    return (
        <button
            type="button"
            onClick={onOpen}
            className="flex h-10 w-full items-center gap-3 px-3 text-left text-sm hover:bg-accent/50"
        >
            <PriorityIcon priorityId={ticket.priority_id} />
            <span className="w-12 shrink-0 font-mono text-xs text-subtle-foreground">
                #{ticket.number}
            </span>
            <span
                className={cn(
                    'min-w-0 truncate',
                    isClosed && 'text-muted-foreground line-through',
                )}
            >
                {ticket.title}
            </span>
            {isFavorite && (
                <Star className="size-3 shrink-0 fill-amber-400 text-amber-400" />
            )}
            <SubtaskProgress
                done={ticket.closed_subtasks_count}
                total={ticket.subtasks_count}
            />
            <LabelChips
                labels={ticket.labels}
                className="hidden flex-nowrap md:flex"
            />
            <span className="ml-auto flex shrink-0 items-center gap-3">
                <span className="hidden max-w-40 lg:inline-flex">
                    {ticket.project_name ? (
                        <ProjectTag
                            name={ticket.project_name}
                            color={ticket.project_color}
                        />
                    ) : (
                        <span className="truncate text-xs text-muted-foreground">
                            {ticket.customer_name}
                        </span>
                    )}
                </span>
                <DueChip dueOn={ticket.due_on} isClosed={isClosed} />
                <AssigneeAvatar name={ticket.assignee_name} />
            </span>
        </button>
    );
}
