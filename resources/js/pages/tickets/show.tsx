import { Head, setLayoutProps } from '@inertiajs/react';
import { TicketDetails } from '@/components/tickets/ticket-drawer';
import { index, show } from '@/routes/tickets';
import type { TicketDrawerData, TicketPickerOptions } from '@/types';

/**
 * A ticket's own page: the same details as the drawer's full-page view,
 * every field editable in place.
 */
export default function ShowTicket({
    drawer: ticket,
    options,
}: {
    drawer: TicketDrawerData;
    options: TicketPickerOptions;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tickets', href: index() },
            { title: `#${ticket.number}`, href: show(ticket.id) },
        ],
    });

    return (
        <>
            <Head title={`#${ticket.number} ${ticket.title}`} />

            <div className="p-0 sm:p-6">
                <article className="mx-auto max-w-[980px] overflow-hidden bg-card sm:rounded-xl sm:border sm:shadow-xs">
                    <TicketDetails
                        key={ticket.id}
                        ticket={ticket}
                        options={options}
                        layout="page"
                    />
                </article>
            </div>
        </>
    );
}
