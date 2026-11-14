import { Head, setLayoutProps } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import TicketForm from '@/components/tickets/ticket-form';
import UpdateTicketController from '@/actions/App/Ticket/Controllers/UpdateTicketController';
import { edit, index, show } from '@/routes/tickets';
import type { Ticket, TicketFormOptions } from '@/types';

export default function EditTicket({
    ticket,
    ...options
}: TicketFormOptions & { ticket: Ticket }) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tickets', href: index() },
            { title: `#${ticket.number}`, href: show(ticket.id) },
            { title: 'Edit', href: edit(ticket.id) },
        ],
    });

    return (
        <>
            <Head title={`Edit ticket #${ticket.number}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title={`Edit ticket #${ticket.number}`} />
                <TicketForm
                    action={UpdateTicketController.form(ticket.id)}
                    ticket={ticket}
                    options={options}
                    submitLabel="Save"
                    cancelHref={show(ticket.id).url}
                />
            </div>
        </>
    );
}
