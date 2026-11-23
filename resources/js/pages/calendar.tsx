import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import PageHeader from '@/components/page-header';
import TicketCalendar from '@/components/planning/ticket-calendar';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketToolbar from '@/components/tickets/ticket-toolbar';
import { Button } from '@/components/ui/button';
import { calendar as calendarRoute } from '@/routes';
import type {
    CalendarWindow,
    LeaveCalendarEntry,
    ProjectOption,
    TicketDrawerData,
    TicketFilters,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

export default function CalendarPage({
    calendar,
    filters,
    tickets,
    projects,
    favoriteTicketIds,
    leave,
    options,
    drawer,
}: {
    calendar: CalendarWindow;
    filters: TicketFilters;
    tickets: TicketListItem[];
    projects: ProjectOption[];
    favoriteTicketIds: string[];
    leave: LeaveCalendarEntry[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openCreateTicket } = useCreateTicket();

    return (
        <>
            <Head title="Calendar" />

            <div className="flex flex-1 flex-col">
                <div className="px-4 pt-6 pb-3 md:px-6">
                    <PageHeader
                        title="Calendar"
                        description="Tickets on the day they're due, and who is on leave. Drag a ticket to another day to reschedule it."
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
                    forCalendar
                />
                <TicketCalendar
                    calendar={calendar}
                    tickets={tickets}
                    projects={projects}
                    favoriteIds={favoriteTicketIds}
                    leave={leave}
                    fullBleed
                />
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

CalendarPage.layout = {
    breadcrumbs: [{ title: 'Calendar', href: calendarRoute() }],
};
