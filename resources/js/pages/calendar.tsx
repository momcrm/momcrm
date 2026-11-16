import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import TicketCalendar from '@/components/planning/ticket-calendar';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import { calendar as calendarRoute } from '@/routes';
import type {
    CalendarWindow,
    ProjectOption,
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

export default function CalendarPage({
    calendar,
    tickets,
    projects,
    favoriteTicketIds,
    options,
    drawer,
}: {
    calendar: CalendarWindow;
    tickets: TicketListItem[];
    projects: ProjectOption[];
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    return (
        <>
            <Head title="Calendar" />

            <div className="flex flex-1 flex-col">
                <div className="px-4 pt-6 pb-3 md:px-6">
                    <PageHeader
                        title="Calendar"
                        description="Tickets on the day they're due. Drag one to another day to reschedule it."
                    />
                </div>
                <TicketCalendar
                    calendar={calendar}
                    tickets={tickets}
                    projects={projects}
                    favoriteIds={favoriteTicketIds}
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
