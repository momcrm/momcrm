import {
    useTicketPriorities,
    usePriority,
} from '@/components/tickets/ticket-meta';
import { Badge } from '@/components/ui/badge';
import { colorSoft } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { TicketStatus } from '@/types';

const statusLabels: Record<TicketStatus, string> = {
    new: 'New',
    in_progress: 'In progress',
    waiting_on_customer: 'Waiting on customer',
    waiting_on_supplier: 'Waiting on supplier',
    closed: 'Closed',
};

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
    return (
        <Badge variant={status === 'closed' ? 'outline' : 'secondary'}>
            {statusLabels[status]}
        </Badge>
    );
}

/**
 * Shown only for priorities more urgent than the team's default, so the page
 * stays quiet.
 */
export function TicketPriorityBadge({
    priorityId,
}: {
    priorityId: string | null;
}) {
    const priority = usePriority(priorityId);
    const standard = useTicketPriorities().find((choice) => choice.is_default);

    if (!priority || !standard || priority.position >= standard.position) {
        return null;
    }

    return (
        <Badge
            variant="outline"
            className={cn('border-transparent', colorSoft[priority.color])}
        >
            {priority.name}
        </Badge>
    );
}
