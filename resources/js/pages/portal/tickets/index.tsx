import { Head, Link, router } from '@inertiajs/react';
import { Plus, Ticket } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { timeAgo } from '@/lib/format';
import { create, index, show } from '@/routes/portal/tickets';
import type { PortalTicket } from '@/types';

type Props = {
    status: 'open' | 'closed';
    tickets: PortalTicket[];
    canCreate: boolean;
};

/**
 * The customer's tickets, open or closed.
 */
export default function PortalTickets({ status, tickets, canCreate }: Props) {
    return (
        <>
            <Head title="Tickets" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-semibold">Tickets</h1>
                <div className="flex items-center gap-2">
                    <ToggleGroup
                        type="single"
                        variant="segmented"
                        value={status}
                        onValueChange={(value) =>
                            value &&
                            value !== status &&
                            router.get(
                                index.url({
                                    query:
                                        value === 'closed'
                                            ? { status: 'closed' }
                                            : {},
                                }),
                            )
                        }
                    >
                        <ToggleGroupItem value="open">Open</ToggleGroupItem>
                        <ToggleGroupItem value="closed">Closed</ToggleGroupItem>
                    </ToggleGroup>
                    {canCreate && (
                        <Button asChild size="sm">
                            <Link href={create()}>
                                <Plus />
                                New ticket
                            </Link>
                        </Button>
                    )}
                </div>
            </div>

            {tickets.length === 0 ? (
                <EmptyState
                    icon={Ticket}
                    title={
                        status === 'open'
                            ? 'No open tickets'
                            : 'No closed tickets'
                    }
                    description={
                        status === 'open'
                            ? 'Raise a ticket when you need a hand, and follow it here.'
                            : 'Tickets show here once they are closed.'
                    }
                />
            ) : (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {tickets.map((ticket) => (
                        <li key={ticket.id}>
                            <Link
                                href={show(ticket.id)}
                                className="flex items-center gap-3 px-4 py-3 hover:bg-accent"
                            >
                                <span className="w-14 shrink-0 text-sm text-muted-foreground tabular-nums">
                                    #{ticket.number}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {ticket.title}
                                    </span>
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {ticket.raised_by
                                            ? `Raised by ${ticket.raised_by} · `
                                            : ''}
                                        Updated {timeAgo(ticket.updated_at)}
                                    </span>
                                </span>
                                <StatusPill hue={ticket.status.hue}>
                                    {ticket.status.name}
                                </StatusPill>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
