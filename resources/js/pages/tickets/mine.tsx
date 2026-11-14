import { Head } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketGroups from '@/components/tickets/ticket-groups';
import type { TicketGroup } from '@/components/tickets/ticket-groups';
import { daysUntil } from '@/components/tickets/ticket-meta';
import { mine } from '@/routes/tickets';
import type {
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

/**
 * Puts someone's tickets in when-they-are-due order: overdue, today, the
 * coming week, later, no date, then what they finished recently.
 */
function groupByDue(tickets: TicketListItem[]): TicketGroup[] {
    const groups: TicketGroup[] = [
        { key: 'overdue', label: 'Overdue', tone: 'destructive', tickets: [] },
        { key: 'today', label: 'Today', tone: 'warning', tickets: [] },
        { key: 'week', label: 'Next 7 days', tone: 'primary', tickets: [] },
        { key: 'later', label: 'Later', tone: 'info', tickets: [] },
        { key: 'undated', label: 'No due date', tone: 'neutral', tickets: [] },
        {
            key: 'done',
            label: 'Completed',
            tone: 'success',
            tickets: [],
            collapsed: true,
        },
    ];
    const byKey = Object.fromEntries(groups.map((group) => [group.key, group]));

    for (const ticket of tickets) {
        const days = ticket.due_on === null ? null : daysUntil(ticket.due_on);
        const key =
            ticket.status === 'closed'
                ? 'done'
                : days === null
                  ? 'undated'
                  : days < 0
                    ? 'overdue'
                    : days === 0
                      ? 'today'
                      : days <= 7
                        ? 'week'
                        : 'later';

        byKey[key].tickets.push(ticket);
    }

    return groups;
}

export default function MyTickets({
    tickets,
    favoriteTicketIds,
    options,
    drawer,
}: {
    tickets: TicketListItem[];
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    return (
        <>
            <Head title="My tickets" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="My tickets"
                    description="Everything assigned to you, by when it's due."
                />

                {tickets.length === 0 ? (
                    <EmptyState
                        title="Nothing assigned to you"
                        description="Tickets assigned to you will show up here, soonest first."
                    />
                ) : (
                    <TicketGroups
                        groups={groupByDue(tickets)}
                        favoriteIds={favoriteTicketIds}
                    />
                )}
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

MyTickets.layout = {
    breadcrumbs: [{ title: 'My tickets', href: mine() }],
};
