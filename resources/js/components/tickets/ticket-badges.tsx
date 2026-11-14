import { Badge } from '@/components/ui/badge';
import type { TicketPriority, TicketStatus } from '@/types';

const statusLabels: Record<TicketStatus, string> = {
    new: 'New',
    in_progress: 'In progress',
    waiting_on_customer: 'Waiting on customer',
    waiting_on_supplier: 'Waiting on supplier',
    closed: 'Closed',
};

const priorityLabels: Record<TicketPriority, string> = {
    low: 'Low',
    normal: 'Normal',
    high: 'High',
    urgent: 'Urgent',
};

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
    return (
        <Badge variant={status === 'closed' ? 'outline' : 'secondary'}>
            {statusLabels[status]}
        </Badge>
    );
}

/**
 * Shown only for priorities above normal, so the list stays quiet.
 */
export function TicketPriorityBadge({
    priority,
}: {
    priority: TicketPriority;
}) {
    if (priority === 'low' || priority === 'normal') {
        return null;
    }

    return (
        <Badge variant={priority === 'urgent' ? 'destructive' : 'default'}>
            {priorityLabels[priority]}
        </Badge>
    );
}
