import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import TicketViews from '@/components/tickets/ticket-views';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/tickets';
import type {
    Paginated,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

export default function TicketsIndex(props: {
    tickets: Paginated<TicketListItem>;
    filters: TicketFilters;
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openCreateTicket } = useCreateTicket();

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

                <TicketViews
                    {...props}
                    emptyState={
                        <EmptyState
                            title="Nothing in the queue"
                            description="Open a ticket for every request or job, and your team can pick it up."
                            action={
                                <Button onClick={() => openCreateTicket()}>
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

TicketsIndex.layout = {
    breadcrumbs: [{ title: 'Tickets', href: index() }],
};
