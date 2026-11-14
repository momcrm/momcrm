import { Head } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import TicketTimeline from '@/components/planning/ticket-timeline';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import { timeline as timelineRoute } from '@/routes';
import type {
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
    TimelineMilestone,
    TimelineProject,
    TimelineWindow,
} from '@/types';

export default function TimelinePage({
    timeline,
    tickets,
    projects,
    milestones,
    options,
    drawer,
}: {
    timeline: TimelineWindow;
    tickets: TicketListItem[];
    projects: TimelineProject[];
    milestones: TimelineMilestone[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    return (
        <>
            <Head title="Timeline" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Timeline"
                    description="Every dated ticket by project. Drag a bar to move it, or its ends to change its dates."
                />
                <TicketTimeline
                    timeline={timeline}
                    tickets={tickets}
                    projects={projects}
                    milestones={milestones}
                />
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

TimelinePage.layout = {
    breadcrumbs: [{ title: 'Timeline', href: timelineRoute() }],
};
