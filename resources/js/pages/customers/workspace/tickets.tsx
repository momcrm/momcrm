import { Head, Link, router } from '@inertiajs/react';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import TicketTable from '@/components/tickets/ticket-table';
import { Button } from '@/components/ui/button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { cn } from '@/lib/utils';
import { tickets as ticketsTab } from '@/routes/customers';
import { show as showTicket } from '@/routes/tickets';
import type { CustomerWorkspace, Paginated, TicketListItem } from '@/types';

type Show = 'open' | 'closed' | 'all';

const shows: { value: Show; label: string }[] = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' },
];

export default function CustomerTickets({
    workspace,
    tickets,
    filters,
}: {
    workspace: CustomerWorkspace;
    tickets: Paginated<TicketListItem>;
    filters: { show: Show; search: string | null };
}) {
    const { customer } = workspace;
    const { openCreateTicket } = useCreateTicket();
    useWorkspaceBreadcrumbs(customer, {
        title: 'Tickets',
        href: ticketsTab(customer.id),
    });
    const openTicket = (
        <Button
            variant="outline"
            size="sm"
            onClick={() => openCreateTicket({ customerId: customer.id })}
        >
            Open ticket
        </Button>
    );

    return (
        <>
            <Head title={`${customer.name} · Tickets`} />
            <TabHeader title="Tickets" action={openTicket} />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <nav className="flex gap-1 rounded-lg bg-muted p-1">
                    {shows.map((show) => (
                        <Link
                            key={show.value}
                            href={ticketsTab(customer.id, {
                                query: {
                                    show:
                                        show.value === 'open'
                                            ? undefined
                                            : show.value,
                                    search: filters.search ?? undefined,
                                },
                            })}
                            only={['tickets', 'filters']}
                            preserveState
                            preserveScroll
                            className={cn(
                                'rounded-md px-3 py-1 text-sm',
                                filters.show === show.value
                                    ? 'bg-background shadow-xs'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {show.label}
                        </Link>
                    ))}
                </nav>
                <SearchInput
                    value={filters.search}
                    placeholder="Search tickets"
                    only={['tickets', 'filters']}
                    size="sm"
                />
            </div>

            {tickets.data.length > 0 ? (
                <>
                    <TicketTable
                        tickets={tickets.data}
                        onOpen={(ticket) =>
                            router.visit(showTicket.url(ticket.id))
                        }
                    />
                    <Pager page={tickets} only="tickets" />
                </>
            ) : filters.search || filters.show !== 'open' ? (
                <EmptyState
                    title="No matching tickets"
                    description="Try another search, or look at all tickets."
                />
            ) : (
                <EmptyState
                    title="Nothing in the queue"
                    description={`Open a ticket for every request or job for ${customer.name}.`}
                    action={openTicket}
                />
            )}
        </>
    );
}
