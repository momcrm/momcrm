import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { usePortal } from '@/layouts/portal-layout';
import { timeAgo } from '@/lib/format';
import { formatCents } from '@/lib/money';
import { statement } from '@/routes/portal';
import { create, index, show } from '@/routes/portal/tickets';
import type { PortalTicket } from '@/types';

type Props = {
    tickets: {
        open_count: number;
        recent: PortalTicket[];
        can_create: boolean;
    } | null;
    owing: {
        balance_in_cents: number;
        current_in_cents: number;
        overdue_in_cents: number;
        currency: string;
    } | null;
};

/**
 * The customer portal's home page: what's open and what's owing.
 */
export default function PortalDashboard({ tickets, owing }: Props) {
    const portal = usePortal();

    return (
        <>
            <Head title="Home" />

            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <h1 className="text-xl font-semibold">
                        Hi {portal.contact.name.split(' ')[0]}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        This is {portal.team.name}'s customer portal for{' '}
                        {portal.customer.name}.
                    </p>
                </div>
                {tickets?.can_create && (
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            New ticket
                        </Link>
                    </Button>
                )}
            </div>

            {(tickets || owing) && (
                <section className="grid gap-4 sm:grid-cols-2">
                    {tickets && (
                        <Link
                            href={index()}
                            className="flex flex-col gap-1 rounded-lg border bg-card p-4 hover:bg-accent"
                        >
                            <span className="text-xs text-muted-foreground">
                                Open tickets
                            </span>
                            <span className="text-2xl font-semibold tabular-nums">
                                {tickets.open_count}
                            </span>
                        </Link>
                    )}
                    {owing && (
                        <Link
                            href={statement()}
                            className="flex flex-col gap-1 rounded-lg border bg-card p-4 hover:bg-accent"
                        >
                            <span className="text-xs text-muted-foreground">
                                Owing
                            </span>
                            <span className="text-2xl font-semibold tabular-nums">
                                {formatCents(
                                    owing.balance_in_cents,
                                    owing.currency,
                                )}
                            </span>
                            {owing.overdue_in_cents > 0 && (
                                <span className="text-xs text-destructive">
                                    {formatCents(
                                        owing.overdue_in_cents,
                                        owing.currency,
                                    )}{' '}
                                    overdue
                                </span>
                            )}
                        </Link>
                    )}
                </section>
            )}

            {tickets && tickets.recent.length > 0 && (
                <section className="space-y-2">
                    <h2 className="text-sm font-medium">Latest open tickets</h2>
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                        {tickets.recent.map((ticket) => (
                            <li key={ticket.id}>
                                <Link
                                    href={show(ticket.id)}
                                    className="flex items-center gap-3 px-4 py-3 hover:bg-accent"
                                >
                                    <span className="w-12 shrink-0 text-sm text-muted-foreground tabular-nums">
                                        #{ticket.number}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium">
                                            {ticket.title}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
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
                </section>
            )}
        </>
    );
}
