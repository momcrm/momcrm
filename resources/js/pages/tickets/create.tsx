import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import TicketForm from '@/components/tickets/ticket-form';
import { Button } from '@/components/ui/button';
import StoreTicketController from '@/actions/App/Ticket/Controllers/StoreTicketController';
import { create as createCustomer } from '@/routes/customers';
import { create, index } from '@/routes/tickets';
import type { TicketFormOptions } from '@/types';

export default function CreateTicket({
    customerId,
    ...options
}: TicketFormOptions & { customerId: string | null }) {
    return (
        <>
            <Head title="Open ticket" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Open ticket"
                    description="Log a request or job so your team can pick it up."
                />
                {options.customers.length === 0 ? (
                    <EmptyState
                        title="Add a customer first"
                        description="Every ticket is for a customer."
                        action={
                            <Button asChild>
                                <Link href={createCustomer()}>
                                    Add customer
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <TicketForm
                        action={StoreTicketController.form()}
                        options={options}
                        customerId={customerId}
                        submitLabel="Open ticket"
                        cancelHref={index().url}
                    />
                )}
            </div>
        </>
    );
}

CreateTicket.layout = {
    breadcrumbs: [
        { title: 'Tickets', href: index() },
        { title: 'Open ticket', href: create() },
    ],
};
