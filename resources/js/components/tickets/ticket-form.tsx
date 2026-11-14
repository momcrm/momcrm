import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Ticket, TicketFormOptions } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

export default function TicketForm({
    action,
    ticket,
    options,
    customerId,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    ticket?: Ticket;
    options: TicketFormOptions;
    customerId?: string | null;
    submitLabel: string;
    cancelHref: string;
}) {
    const [selectedCustomer, setSelectedCustomer] = useState(
        ticket?.customer_id ?? customerId ?? '',
    );

    const contacts = options.contacts.filter(
        (contact) => contact.customer_id === selectedCustomer,
    );
    const locations = options.locations.filter(
        (location) => location.customer_id === selectedCustomer,
    );

    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <FormField
                        id="customer_id"
                        label="Customer"
                        error={errors.customer_id}
                    >
                        <NativeSelect
                            id="customer_id"
                            name="customer_id"
                            value={selectedCustomer}
                            onChange={(event) =>
                                setSelectedCustomer(event.target.value)
                            }
                            required
                        >
                            <option value="" disabled>
                                Choose a customer
                            </option>
                            {options.customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>

                    <FormField
                        id="title"
                        label="Title"
                        hint="A short summary, for example Printer offline in reception."
                        error={errors.title}
                    >
                        <Input
                            id="title"
                            name="title"
                            defaultValue={ticket?.title}
                            required
                            autoFocus
                        />
                    </FormField>

                    <FormField
                        id="description"
                        label="Details"
                        error={errors.description}
                    >
                        <Textarea
                            id="description"
                            name="description"
                            rows={6}
                            defaultValue={ticket?.description ?? ''}
                        />
                    </FormField>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="contact_id"
                            label="Contact"
                            error={errors.contact_id}
                        >
                            <NativeSelect
                                key={`contact-${selectedCustomer}`}
                                id="contact_id"
                                name="contact_id"
                                defaultValue={ticket?.contact_id ?? ''}
                                disabled={contacts.length === 0}
                            >
                                <option value="">No contact</option>
                                {contacts.map((contact) => (
                                    <option key={contact.id} value={contact.id}>
                                        {contact.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="location_id"
                            label="Location"
                            error={errors.location_id}
                        >
                            <NativeSelect
                                key={`location-${selectedCustomer}`}
                                id="location_id"
                                name="location_id"
                                defaultValue={ticket?.location_id ?? ''}
                                disabled={locations.length === 0}
                            >
                                <option value="">No particular location</option>
                                {locations.map((location) => (
                                    <option
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="assignee_id"
                            label="Assigned to"
                            error={errors.assignee_id}
                        >
                            <NativeSelect
                                id="assignee_id"
                                name="assignee_id"
                                defaultValue={ticket?.assignee_id ?? ''}
                            >
                                <option value="">Unassigned</option>
                                {options.members.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="priority"
                            label="Priority"
                            error={errors.priority}
                        >
                            <NativeSelect
                                id="priority"
                                name="priority"
                                defaultValue={ticket?.priority ?? 'normal'}
                            >
                                {options.priorities.map((priority) => (
                                    <option
                                        key={priority.value}
                                        value={priority.value}
                                    >
                                        {priority.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        {ticket && (
                            <FormField
                                id="status"
                                label="Status"
                                error={errors.status}
                            >
                                <NativeSelect
                                    id="status"
                                    name="status"
                                    defaultValue={ticket.status}
                                >
                                    {options.statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-ticket-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
