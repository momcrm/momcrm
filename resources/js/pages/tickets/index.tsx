import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import TicketList from '@/components/tickets/ticket-list';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { create, index } from '@/routes/tickets';
import type { Paginated, TicketListItem } from '@/types';

type Filter = 'open' | 'closed' | 'all';

const filters: { value: Filter; label: string }[] = [
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' },
];

export default function TicketsIndex({
    tickets,
    filters: current,
}: {
    tickets: Paginated<TicketListItem>;
    filters: { search: string | null; status: Filter };
}) {
    return (
        <>
            <Head title="Tickets" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Tickets"
                    description="Every request and job for your customers."
                    actions={
                        <Button asChild>
                            <Link href={create()}>Open ticket</Link>
                        </Button>
                    }
                />

                <div className="flex flex-wrap items-center gap-4">
                    <nav className="flex gap-1 rounded-lg bg-muted p-1">
                        {filters.map((filter) => (
                            <Link
                                key={filter.value}
                                href={index({
                                    query: {
                                        status:
                                            filter.value === 'open'
                                                ? undefined
                                                : filter.value,
                                    },
                                })}
                                preserveState
                                className={cn(
                                    'rounded-md px-3 py-1 text-sm',
                                    current.status === filter.value
                                        ? 'bg-background shadow-xs'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {filter.label}
                            </Link>
                        ))}
                    </nav>
                    <SearchInput
                        value={current.search}
                        placeholder="Search by title or number"
                        only={['tickets', 'filters']}
                    />
                </div>

                {tickets.data.length === 0 ? (
                    current.search ? (
                        <EmptyState
                            title={`No matches for "${current.search}"`}
                            description="Check the spelling or try a shorter search."
                        />
                    ) : current.status === 'closed' ? (
                        <EmptyState
                            title="No closed tickets"
                            description="Tickets you close will show up here."
                        />
                    ) : (
                        <EmptyState
                            title="Nothing in the queue"
                            description="Open a ticket for every request or job, and your team can pick it up."
                            action={
                                <Button asChild>
                                    <Link href={create()}>Open ticket</Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <>
                        <TicketList tickets={tickets.data} />
                        <Pager page={tickets} only="tickets" />
                    </>
                )}
            </div>
        </>
    );
}

TicketsIndex.layout = {
    breadcrumbs: [{ title: 'Tickets', href: index() }],
};
