import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import PageHeader from '@/components/page-header';
import {
    TicketPriorityBadge,
    TicketStatusBadge,
} from '@/components/tickets/ticket-badges';
import { Button } from '@/components/ui/button';
import CloseTicketController from '@/actions/App/Ticket/Controllers/CloseTicketController';
import ReopenTicketController from '@/actions/App/Ticket/Controllers/ReopenTicketController';
import { show as showCustomer } from '@/routes/customers';
import { edit, index, show } from '@/routes/tickets';
import type { Contact, Customer, Location, Option, Ticket } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export default function ShowTicket({
    ticket,
    customer,
    contact,
    location,
    assigneeName,
    priorities,
}: {
    ticket: Ticket;
    customer: Customer;
    contact: Contact | null;
    location: Location | null;
    assigneeName: string | null;
    statuses: Option[];
    priorities: Option[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tickets', href: index() },
            { title: `#${ticket.number}`, href: show(ticket.id) },
        ],
    });

    const isClosed = ticket.status === 'closed';

    const details = [
        {
            label: 'Customer',
            value: (
                <Link
                    href={showCustomer(customer.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {customer.name}
                </Link>
            ),
        },
        {
            label: 'Contact',
            value: contact
                ? [contact.full_name, contact.email, contact.work_phone]
                      .filter(Boolean)
                      .join(' · ')
                : '—',
        },
        { label: 'Location', value: location?.name ?? '—' },
        { label: 'Assigned to', value: assigneeName ?? 'Unassigned' },
        {
            label: 'Priority',
            value:
                priorities.find((option) => option.value === ticket.priority)
                    ?.label ?? ticket.priority,
        },
        {
            label: 'Opened',
            value: ticket.created_at
                ? dateFormatter.format(new Date(ticket.created_at))
                : '—',
        },
        ...(ticket.closed_at
            ? [
                  {
                      label: 'Closed',
                      value: dateFormatter.format(new Date(ticket.closed_at)),
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title={`#${ticket.number} ${ticket.title}`} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={ticket.title}
                    description={
                        <span className="flex items-center gap-2">
                            Ticket #{ticket.number}
                            <TicketStatusBadge status={ticket.status} />
                            <TicketPriorityBadge priority={ticket.priority} />
                        </span>
                    }
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={edit(ticket.id)}>Edit</Link>
                            </Button>
                            <Form
                                {...(isClosed
                                    ? ReopenTicketController.form(ticket.id)
                                    : CloseTicketController.form(ticket.id))}
                            >
                                {({ processing }) => (
                                    <Button
                                        disabled={processing}
                                        variant={
                                            isClosed ? 'outline' : 'default'
                                        }
                                        data-test="toggle-ticket-button"
                                    >
                                        {isClosed
                                            ? 'Reopen ticket'
                                            : 'Close ticket'}
                                    </Button>
                                )}
                            </Form>
                        </>
                    }
                />

                <dl className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                    {details.map((detail) => (
                        <div key={detail.label}>
                            <dt className="text-sm text-muted-foreground">
                                {detail.label}
                            </dt>
                            <dd className="break-words">{detail.value}</dd>
                        </div>
                    ))}
                </dl>

                <section className="space-y-2">
                    <h2 className="font-medium">Details</h2>
                    <p className="text-sm whitespace-pre-line">
                        {ticket.description || (
                            <span className="text-muted-foreground">
                                No details added.
                            </span>
                        )}
                    </p>
                </section>
            </div>
        </>
    );
}
