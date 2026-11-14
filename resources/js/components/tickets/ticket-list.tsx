import { Link } from '@inertiajs/react';
import {
    TicketPriorityBadge,
    TicketStatusBadge,
} from '@/components/tickets/ticket-badges';
import { show } from '@/routes/tickets';
import type { TicketListItem } from '@/types';

export default function TicketList({
    tickets,
    showCustomer = true,
}: {
    tickets: TicketListItem[];
    showCustomer?: boolean;
}) {
    return (
        <ul className="divide-y rounded-lg border">
            {tickets.map((ticket) => (
                <li key={ticket.id}>
                    <Link
                        href={show(ticket.id)}
                        className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
                        prefetch
                    >
                        <div className="min-w-0">
                            <p className="truncate font-medium">
                                <span className="text-muted-foreground">
                                    #{ticket.number}
                                </span>{' '}
                                {ticket.title}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {[
                                    showCustomer && ticket.customer_name,
                                    ticket.assignee_name ?? 'Unassigned',
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <TicketPriorityBadge priority={ticket.priority} />
                            <TicketStatusBadge status={ticket.status} />
                        </div>
                    </Link>
                </li>
            ))}
        </ul>
    );
}
