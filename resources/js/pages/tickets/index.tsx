import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import TicketBoard from '@/components/tickets/ticket-board';
import {
    TicketBulkBar,
    useTicketSelection,
} from '@/components/tickets/ticket-bulk';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketList from '@/components/tickets/ticket-list';
import TicketTable from '@/components/tickets/ticket-table';
import TicketToolbar from '@/components/tickets/ticket-toolbar';
import { Button } from '@/components/ui/button';
import { useTicketViewPrefs } from '@/hooks/use-ticket-view-prefs';
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
    const prefs = useTicketViewPrefs();
    const canPick = filters.view !== 'board';
    const selection = useTicketSelection(
        canPick ? tickets.data.map((ticket) => ticket.id) : [],
    );
    const isFiltered =
        Boolean(filters.search) ||
        filters.priority.length > 0 ||
        filters.assignee !== null ||
        filters.project !== null ||
        filters.label !== null ||
        filters.type !== null ||
        filters.group !== null;

    return (
        <>
            <Head title="Tickets" />

            <div className="flex flex-1 flex-col">
                <div className="px-4 pt-6 pb-3 md:px-6">
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
                </div>

                <TicketToolbar
                    filters={filters}
                    options={options}
                    prefs={prefs}
                />

                {tickets.data.length === 0 && filters.view !== 'board' ? (
                    <div className="p-4 md:p-6">
                        {isFiltered ? (
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
                        )}
                    </div>
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
                            flush
                            tickets={tickets.data}
                            groupBy={prefs.groupBy}
                            hiddenColumns={prefs.hiddenColumns}
                            selection={selection}
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
                        <div className="px-4 py-3 md:px-6">
                            <Pager page={tickets} only="tickets" />
                        </div>
                    </>
                ) : (
                    <TicketList
                        key={filters.status}
                        tickets={tickets.data}
                        favoriteIds={favoriteTicketIds}
                        options={options}
                        groupBy={prefs.groupBy}
                        collapseClosed={filters.status === 'all'}
                        selection={selection}
                        projectId={
                            filters.project && filters.project !== 'none'
                                ? filters.project
                                : null
                        }
                    />
                )}
            </div>

            {canPick && (
                <TicketBulkBar selection={selection} options={options} />
            )}
            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

TicketsIndex.layout = {
    breadcrumbs: [{ title: 'Tickets', href: index() }],
};
