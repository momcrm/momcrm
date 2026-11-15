import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import {
    useTicketPriorities,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
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
    const priorities = useTicketPriorities();
    const statuses = useTicketStatuses();
    const [selectedCustomer, setSelectedCustomer] = useState(
        ticket?.customer_id ?? customerId ?? '',
    );
    const [selectedProject, setSelectedProject] = useState(
        ticket?.project_id ?? '',
    );

    const chooseProject = (projectId: string) => {
        setSelectedProject(projectId);

        const projectCustomer = options.projects.find(
            (project) => project.id === projectId,
        )?.customer_id;

        if (projectCustomer && !selectedCustomer) {
            setSelectedCustomer(projectCustomer);
        }
    };

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
                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="project_id"
                            label="Project"
                            error={errors.project_id}
                        >
                            <NativeSelect
                                id="project_id"
                                name="project_id"
                                value={selectedProject}
                                onChange={(event) =>
                                    chooseProject(event.target.value)
                                }
                            >
                                <option value="">No project</option>
                                {options.projects.map((project) => (
                                    <option key={project.id} value={project.id}>
                                        {project.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="customer_id"
                            label="Customer"
                            hint={
                                selectedProject
                                    ? 'Optional for project tickets.'
                                    : undefined
                            }
                            error={errors.customer_id}
                        >
                            <NativeSelect
                                id="customer_id"
                                name="customer_id"
                                value={selectedCustomer}
                                onChange={(event) =>
                                    setSelectedCustomer(event.target.value)
                                }
                                required={!selectedProject}
                            >
                                <option value="" disabled={!selectedProject}>
                                    {selectedProject
                                        ? 'No customer'
                                        : 'Choose a customer'}
                                </option>
                                {options.customers.map((customer) => (
                                    <option
                                        key={customer.id}
                                        value={customer.id}
                                    >
                                        {customer.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                    </div>

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
                            id="priority_id"
                            label="Priority"
                            error={errors.priority_id}
                        >
                            <NativeSelect
                                id="priority_id"
                                name="priority_id"
                                defaultValue={
                                    ticket?.priority_id ??
                                    priorities.find(
                                        (priority) => priority.is_default,
                                    )?.id
                                }
                            >
                                {priorities.map((priority) => (
                                    <option
                                        key={priority.id}
                                        value={priority.id}
                                    >
                                        {priority.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="starts_on"
                            label="Start date"
                            error={errors.starts_on}
                        >
                            <Input
                                id="starts_on"
                                name="starts_on"
                                type="date"
                                defaultValue={ticket?.starts_on ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="due_on"
                            label="Due date"
                            error={errors.due_on}
                        >
                            <Input
                                id="due_on"
                                name="due_on"
                                type="date"
                                defaultValue={ticket?.due_on ?? ''}
                            />
                        </FormField>
                        {ticket && (
                            <FormField
                                id="status_id"
                                label="Status"
                                error={errors.status_id}
                            >
                                <NativeSelect
                                    id="status_id"
                                    name="status_id"
                                    defaultValue={ticket.status_id ?? undefined}
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.id}
                                            value={status.id}
                                        >
                                            {status.name}
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
