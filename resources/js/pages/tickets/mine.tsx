import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import TicketViews from '@/components/tickets/ticket-views';
import { Button } from '@/components/ui/button';
import { calendar } from '@/routes';
import { mine } from '@/routes/tickets';
import type {
    Auth,
    Paginated,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

/**
 * The tickets page's views, limited to the tickets assigned to you.
 */
export default function MyTickets(props: {
    tickets: Paginated<TicketListItem>;
    filters: TicketFilters;
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { openCreateTicket } = useCreateTicket();
    const assignToMe = () => openCreateTicket({ assigneeId: auth.user.id });

    return (
        <>
            <Head title="My tickets" />

            <div className="flex flex-1 flex-col">
                <div className="px-4 pt-6 pb-3 md:px-6">
                    <PageHeader
                        title="My tickets"
                        description="Everything assigned to you."
                        actions={
                            <>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={calendar({ query: { mine: 1 } })}
                                    >
                                        <CalendarDays />
                                        Calendar
                                    </Link>
                                </Button>
                                <Button onClick={assignToMe}>
                                    <Plus />
                                    New ticket
                                </Button>
                            </>
                        }
                    />
                </div>

                <TicketViews
                    {...props}
                    fixedAssignee
                    emptyState={
                        <EmptyState
                            title="Nothing assigned to you"
                            description="Tickets assigned to you will show up here."
                            action={
                                <Button onClick={assignToMe}>
                                    Open ticket
                                </Button>
                            }
                        />
                    }
                />
            </div>
        </>
    );
}

MyTickets.layout = {
    breadcrumbs: [{ title: 'My tickets', href: mine() }],
};
