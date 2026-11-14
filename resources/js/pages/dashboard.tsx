import { Head, Link, usePage } from '@inertiajs/react';
import GettingStartedChecklist from '@/components/getting-started-checklist';
import SecurityNudge from '@/components/security-nudge';
import TicketList from '@/components/tickets/ticket-list';
import { Button } from '@/components/ui/button';
import { formatCents } from '@/lib/money';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as invoicesIndex } from '@/routes/invoices';
import {
    create as createTicket,
    index as ticketsIndex,
} from '@/routes/tickets';
import type { TicketListItem } from '@/types';

type Stats = {
    customers: number;
    open_tickets: number;
    unpaid_invoices: number;
    unpaid_total_in_cents: number;
    overdue_invoices: number;
};

function StatCard({
    label,
    value,
    detail,
    href,
}: {
    label: string;
    value: string;
    detail?: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="flex flex-col gap-1 rounded-xl border p-4 hover:bg-accent"
        >
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-2xl font-semibold tabular-nums">{value}</span>
            {detail && (
                <span className="text-sm text-muted-foreground">{detail}</span>
            )}
        </Link>
    );
}

export default function Dashboard({
    preferredName,
    stats,
    myTickets,
    hasSecurity,
}: {
    preferredName: string;
    stats: Stats;
    myTickets: TicketListItem[];
    hasSecurity: boolean;
}) {
    const { gettingStarted } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                {gettingStarted ? (
                    <GettingStartedChecklist
                        gettingStarted={gettingStarted}
                        preferredName={preferredName}
                    />
                ) : (
                    !hasSecurity && <SecurityNudge />
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        label="Open tickets"
                        value={String(stats.open_tickets)}
                        href={ticketsIndex().url}
                    />
                    <StatCard
                        label="Customers"
                        value={String(stats.customers)}
                        href={customersIndex().url}
                    />
                    <StatCard
                        label="Unpaid invoices"
                        value={formatCents(stats.unpaid_total_in_cents)}
                        detail={
                            stats.overdue_invoices > 0
                                ? `${stats.overdue_invoices} overdue`
                                : `${stats.unpaid_invoices} sent and waiting`
                        }
                        href={invoicesIndex({ query: { status: 'sent' } }).url}
                    />
                </div>

                <section className="space-y-3">
                    <div className="flex items-center justify-between gap-4">
                        <h2 className="font-medium">Assigned to you</h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={createTicket()}>Open ticket</Link>
                        </Button>
                    </div>
                    {myTickets.length > 0 ? (
                        <TicketList tickets={myTickets} />
                    ) : (
                        <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Nothing assigned to you right now.
                        </p>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
