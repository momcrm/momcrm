import type { ReactNode } from 'react';
import EmptyState from '@/components/empty-state';
import Pager from '@/components/pager';
import TicketBoard from '@/components/tickets/ticket-board';
import {
    TicketBulkBar,
    useTicketSelection,
} from '@/components/tickets/ticket-bulk';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketList from '@/components/tickets/ticket-list';
import TicketTable from '@/components/tickets/ticket-table';
import TicketToolbar from '@/components/tickets/ticket-toolbar';
import { useTicketViewPrefs } from '@/hooks/use-ticket-view-prefs';
import { visitWithQuery } from '@/lib/query';
import type {
    Paginated,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

/**
 * A ticket page's views under its header: the toolbar, then the list, board
 * or table, the bulk bar and the drawer. The tickets page shows every
 * ticket; "My tickets" fixes the assignee to the signed-in person.
 */
export default function TicketViews({
    tickets,
    filters,
    favoriteTicketIds,
    options,
    drawer,
    fixedAssignee = false,
    emptyState,
}: {
    tickets: Paginated<TicketListItem>;
    filters: TicketFilters;
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
    fixedAssignee?: boolean;
    /** What an open view with no filters shows when there are no tickets. */
    emptyState: ReactNode;
}) {
    const prefs = useTicketViewPrefs();
    const groupBy =
        fixedAssignee && prefs.groupBy === 'assignee'
            ? 'status'
            : prefs.groupBy;
    const canPick = filters.view !== 'board';
    const selection = useTicketSelection(
        canPick ? tickets.data.map((ticket) => ticket.id) : [],
    );
    const isFiltered =
        Boolean(filters.search) ||
        filters.priority.length > 0 ||
        (!fixedAssignee && filters.assignee !== null) ||
        filters.project !== null ||
        filters.label !== null ||
        filters.type !== null ||
        filters.group !== null;
    const projectId =
        filters.project && filters.project !== 'none' ? filters.project : null;

    return (
        <>
            <TicketToolbar
                filters={filters}
                options={options}
                prefs={{ ...prefs, groupBy }}
                fixedAssignee={fixedAssignee}
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
                        emptyState
                    )}
                </div>
            ) : filters.view === 'board' ? (
                <TicketBoard
                    tickets={tickets.data}
                    favoriteIds={favoriteTicketIds}
                    options={options}
                    projectId={projectId}
                />
            ) : filters.view === 'table' ? (
                <>
                    <TicketTable
                        flush
                        tickets={tickets.data}
                        groupBy={groupBy}
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
                    groupBy={groupBy}
                    collapseClosed={filters.status === 'all'}
                    selection={selection}
                    projectId={projectId}
                />
            )}

            {canPick && (
                <TicketBulkBar selection={selection} options={options} />
            )}
            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}
