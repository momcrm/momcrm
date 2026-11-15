import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import TicketBoard from '@/components/tickets/ticket-board';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketGroups from '@/components/tickets/ticket-groups';
import { useTicketStatuses } from '@/components/tickets/ticket-meta';
import TicketTable from '@/components/tickets/ticket-table';
import TicketToolbar from '@/components/tickets/ticket-toolbar';
import { Button } from '@/components/ui/button';
import { colorDot } from '@/lib/colors';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import { visitWithQuery } from '@/lib/query';
import { index } from '@/routes/tickets';
import type {
    Paginated,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

export default function TicketsIndex({
    tickets,
    filters,
    favoriteTicketIds,
    options,
    drawer,
}: {
    tickets: Paginated<TicketListItem>;
    filters: TicketFilters;
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openCreateTicket } = useCreateTicket();
    const statuses = useTicketStatuses(
        ...tickets.data.map((ticket) => ticket.status_id),
    );
    const isFiltered =
        Boolean(filters.search) ||
        filters.priority.length > 0 ||
        filters.assignee !== null ||
        filters.project !== null ||
        filters.label !== null;

    return (
        <>
            <Head title="Tickets" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Tickets"
                    description="Every request and job, for customers and your own projects."
                    actions={
                        <Button onClick={() => openCreateTicket()}>
                            <Plus />
                            New ticket
                        </Button>
                    }
                />

                <TicketToolbar filters={filters} options={options} />

                {tickets.data.length === 0 && filters.view !== 'board' ? (
                    isFiltered ? (
                        <EmptyState
                            title="No tickets match"
                            description="Try a shorter search or clear a filter."
                        />
                    ) : filters.status === 'closed' ? (
                        <EmptyState
                            title="No closed tickets"
                            description="Tickets you close will show up here."
                        />
                    ) : (
                        <EmptyState
                            title="Nothing in the queue"
                            description="Open a ticket for every request or job, and your team can pick it up."
                            action={
                                <Button onClick={() => openCreateTicket()}>
                                    Open ticket
                                </Button>
                            }
                        />
                    )
                ) : filters.view === 'board' ? (
                    <TicketBoard
                        tickets={tickets.data}
                        favoriteIds={favoriteTicketIds}
                        options={options}
                        projectId={
                            filters.project && filters.project !== 'none'
                                ? filters.project
                                : null
                        }
                    />
                ) : filters.view === 'table' ? (
                    <>
                        <TicketTable
                            tickets={tickets.data}
                            sort={{
                                column: filters.sort,
                                direction: filters.direction,
                            }}
                            onSortChange={({ column, direction }) =>
                                visitWithQuery(
                                    { sort: column, direction },
                                    { only: ['tickets', 'filters'] },
                                )
                            }
                        />
                        <Pager page={tickets} only="tickets" />
                    </>
                ) : (
                    <TicketGroups
                        key={filters.status}
                        favoriteIds={favoriteTicketIds}
                        groups={statuses.map((status) => ({
                            key: status.id,
                            label: status.name,
                            dotClassName: colorDot[status.color],
                            collapsed:
                                status.category === 'closed' &&
                                filters.status === 'all',
                            tickets: tickets.data.filter(
                                (ticket) => ticket.status_id === status.id,
                            ),
                        }))}
                    />
                )}
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

TicketsIndex.layout = {
    breadcrumbs: [{ title: 'Tickets', href: index() }],
};
