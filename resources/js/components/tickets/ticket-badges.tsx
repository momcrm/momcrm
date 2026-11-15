import {
    useTicketPriorities,
    usePriority,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import { Badge } from '@/components/ui/badge';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';

export function TicketStatusBadge({ statusId }: { statusId: string | null }) {
    const status = useTicketStatus(statusId);

    return (
        <Badge
            variant={status?.category === 'closed' ? 'outline' : 'secondary'}
        >
            {status?.name ?? 'No status'}
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
            className={cn('hue-soft', 'border-transparent')}
            style={hueStyle(priority.hue)}
        >
            {priority.name}
        </Badge>
    );
}
