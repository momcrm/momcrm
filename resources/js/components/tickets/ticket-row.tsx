import { Check, Star } from 'lucide-react';
import {
    AssigneeAvatar,
    DueChip,
    PriorityIcon,
    ProjectTag,
    StatusGlyph,
    SubtaskProgress,
    useIsClosedStatus,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import { LabelChips } from '@/components/labels';
import { cn } from '@/lib/utils';
import type { TicketListItem } from '@/types';

/**
 * One line of a ticket list: priority, number, title and labels on the left;
 * project, due date and assignee on the right. With `onToggleClosed` it
 * starts with a round tick box that closes or reopens the ticket, and its
 * status; `showAssignee` leaves the avatar out of lists of one person's tickets.
 */
export default function TicketRow({
    ticket,
    isFavorite,
    onOpen,
    onToggleClosed,
    showAssignee = true,
}: {
    ticket: TicketListItem;
    isFavorite: boolean;
    onOpen: () => void;
    onToggleClosed?: () => void;
    showAssignee?: boolean;
}) {
    const isClosed = useIsClosedStatus()(ticket.status_id);
    const status = useTicketStatus(ticket.status_id);

    const row = (
        <button
            type="button"
            onClick={onOpen}
            className={cn(
                'flex h-10 w-full min-w-0 items-center gap-3 text-left text-sm',
                onToggleClosed ? 'pr-3 pl-2.5' : 'px-3 hover:bg-accent/50',
            )}
        >
            {onToggleClosed && <StatusGlyph status={status} />}
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
                            hue={ticket.project_hue}
                        />
                    ) : (
                        <span className="truncate text-xs text-muted-foreground">
                            {ticket.customer_name}
                        </span>
                    )}
                </span>
                <DueChip dueOn={ticket.due_on} isClosed={isClosed} />
                {showAssignee && <AssigneeAvatar name={ticket.assignee_name} />}
            </span>
        </button>
    );

    if (!onToggleClosed) {
        return row;
    }

    return (
        <div className="flex items-center pl-3 hover:bg-accent/50">
            <button
                type="button"
                role="checkbox"
                aria-checked={isClosed}
                aria-label={`${isClosed ? 'Reopen' : 'Close'} #${ticket.number} ${ticket.title}`}
                onClick={onToggleClosed}
                className={cn(
                    'flex size-4 shrink-0 items-center justify-center rounded-full border-[1.5px] transition-colors',
                    isClosed
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border-strong bg-card hover:border-subtle-foreground',
                )}
            >
                {isClosed && <Check className="size-2.5" strokeWidth={3} />}
            </button>
            {row}
        </div>
    );
}
