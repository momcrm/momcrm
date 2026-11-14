import { Head, Link, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import ContactList from '@/components/contacts/contact-list';
import RecordList from '@/components/customers/record-list';
import DetailSection from '@/components/detail-section';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import InvoiceList from '@/components/invoices/invoice-list';
import {
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
} from '@/components/projects/project-meta';
import TicketList from '@/components/tickets/ticket-list';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DeleteCustomerController from '@/actions/App/Customer/Controllers/DeleteCustomerController';
import { create as createAsset, edit as editAsset } from '@/routes/assets';
import { create as createContact } from '@/routes/contacts';
import { edit, index, show } from '@/routes/customers';
import {
    create as createLocation,
    edit as editLocation,
} from '@/routes/locations';
import {
    create as createService,
    edit as editService,
} from '@/routes/services';
import { formatCents } from '@/lib/money';
import { create as createInvoice } from '@/routes/invoices';
import { show as showProject } from '@/routes/projects';
import { create as createTicket } from '@/routes/tickets';
import type {
    ProjectListItem,
    Asset,
    Contact,
    Customer,
    InvoiceListItem,
    Location,
    Service,
    TicketListItem,
} from '@/types';

const serviceStatusLabels: Record<Service['status'], string> = {
    active: 'Active',
    pending: 'Pending',
    cancelled: 'Cancelled',
};

function billingPeriod(months: number | null): string {
    switch (months) {
        case 1:
            return 'month';
        case 12:
            return 'year';
        default:
            return `${months} months`;
    }
}

function AddButton({ href, label }: { href: string; label: string }) {
    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href}>{label}</Link>
        </Button>
    );
}

export default function ShowCustomer({
    customer,
    contacts,
    locations,
    assets,
    services,
    projects,
    tickets,
    invoices,
}: {
    customer: Customer;
    contacts: Contact[];
    locations: Location[];
    assets: Asset[];
    services: Service[];
    projects: ProjectListItem[];
    tickets: TicketListItem[];
    invoices: InvoiceListItem[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: index() },
            { title: customer.name, href: show(customer.id) },
        ],
    });

    const details = [
        { label: 'Email', value: customer.email },
        { label: 'Phone', value: customer.phone },
        { label: 'Website', value: customer.website },
        { label: 'ABN', value: customer.abn },
    ].filter((detail) => detail.value);

    const addContact = (
        <Button variant="outline" size="sm" asChild>
            <Link href={createContact({ query: { customer: customer.id } })}>
                Add contact
            </Link>
        </Button>
    );

    return (
        <>
            <Head title={customer.name} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={customer.name}
                    description={
                        <span className="flex items-center gap-2">
                            Customer #{customer.number}
                            {customer.status === 'inactive' && (
                                <Badge variant="secondary">Inactive</Badge>
                            )}
                            {customer.is_sample && (
                                <Badge variant="outline">Sample</Badge>
                            )}
                        </span>
                    }
                    actions={
                        <>
                            <Button variant="outline" asChild>
                                <Link href={edit(customer.id)}>Edit</Link>
                            </Button>
                            <ConfirmDelete
                                action={DeleteCustomerController.form(
                                    customer.id,
                                )}
                                title={`Delete ${customer.name}?`}
                                description="Their contacts, locations, assets, services, tickets and invoices are deleted too. This can't be undone."
                            />
                        </>
                    }
                />

                {(details.length > 0 || customer.notes) && (
                    <dl className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                        {details.map((detail) => (
                            <div key={detail.label}>
                                <dt className="text-sm text-muted-foreground">
                                    {detail.label}
                                </dt>
                                <dd className="break-words">{detail.value}</dd>
                            </div>
                        ))}
                        {customer.notes && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm text-muted-foreground">
                                    Notes
                                </dt>
                                <dd className="whitespace-pre-line">
                                    {customer.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                )}

                {projects.length > 0 && (
                    <DetailSection title="Projects">
                        <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                            {projects.map((project) => (
                                <li key={project.id}>
                                    <Link
                                        href={showProject(project.id)}
                                        className="flex h-11 items-center gap-3 px-3 text-sm hover:bg-accent/50"
                                    >
                                        <ProjectIcon
                                            icon={project.icon}
                                            color={project.color}
                                            size="sm"
                                        />
                                        <span className="min-w-0 flex-1 truncate font-medium">
                                            {project.name}
                                        </span>
                                        <span className="hidden w-40 md:block">
                                            <ProgressBar
                                                closed={
                                                    project.closed_tickets_count
                                                }
                                                total={project.tickets_count}
                                            />
                                        </span>
                                        <ProjectStatusPill
                                            status={project.status}
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </DetailSection>
                )}

                <DetailSection
                    title="Recent tickets"
                    action={
                        tickets.length > 0 && (
                            <AddButton
                                href={
                                    createTicket({
                                        query: { customer: customer.id },
                                    }).url
                                }
                                label="Open ticket"
                            />
                        )
                    }
                >
                    {tickets.length > 0 ? (
                        <TicketList tickets={tickets} showCustomer={false} />
                    ) : (
                        <EmptyState
                            title="Nothing in the queue"
                            description={`Open a ticket for every request or job for ${customer.name}.`}
                            action={
                                <AddButton
                                    href={
                                        createTicket({
                                            query: { customer: customer.id },
                                        }).url
                                    }
                                    label="Open ticket"
                                />
                            }
                        />
                    )}
                </DetailSection>

                <DetailSection
                    title="Recent invoices"
                    action={
                        invoices.length > 0 && (
                            <AddButton
                                href={
                                    createInvoice({
                                        query: { customer: customer.id },
                                    }).url
                                }
                                label="Create invoice"
                            />
                        )
                    }
                >
                    {invoices.length > 0 ? (
                        <InvoiceList invoices={invoices} showCustomer={false} />
                    ) : (
                        <EmptyState
                            title="No invoices yet"
                            description={`Invoices for ${customer.name} will show up here once you bill them.`}
                            action={
                                <AddButton
                                    href={
                                        createInvoice({
                                            query: { customer: customer.id },
                                        }).url
                                    }
                                    label="Create invoice"
                                />
                            }
                        />
                    )}
                </DetailSection>

                <DetailSection
                    title="Contacts"
                    action={contacts.length > 0 && addContact}
                >
                    {contacts.length > 0 ? (
                        <ContactList contacts={contacts} />
                    ) : (
                        <EmptyState
                            title="No contacts yet"
                            description="Add the people you deal with at this customer, so you know who to call."
                            action={addContact}
                        />
                    )}
                </DetailSection>

                <DetailSection
                    title="Locations"
                    action={
                        locations.length > 0 && (
                            <AddButton
                                href={createLocation(customer.id).url}
                                label="Add location"
                            />
                        )
                    }
                >
                    {locations.length > 0 ? (
                        <RecordList
                            items={locations.map((location) => ({
                                id: location.id,
                                href: editLocation(location.id).url,
                                title: location.name,
                                detail: [
                                    location.street_one,
                                    location.suburb,
                                    location.state,
                                    location.postcode,
                                ]
                                    .filter(Boolean)
                                    .join(', '),
                                badge: location.is_primary && (
                                    <Badge variant="secondary">Main</Badge>
                                ),
                            }))}
                        />
                    ) : (
                        <EmptyState
                            title={`No locations for ${customer.name}`}
                            description="Add an office or site so tickets and assets know where they belong."
                            action={
                                <AddButton
                                    href={createLocation(customer.id).url}
                                    label="Add location"
                                />
                            }
                        />
                    )}
                </DetailSection>

                <DetailSection
                    title="Assets"
                    action={
                        assets.length > 0 && (
                            <AddButton
                                href={createAsset(customer.id).url}
                                label="Add asset"
                            />
                        )
                    }
                >
                    {assets.length > 0 ? (
                        <RecordList
                            items={assets.map((asset) => ({
                                id: asset.id,
                                href: editAsset(asset.id).url,
                                title: asset.name,
                                detail: [
                                    asset.serial_number,
                                    locations.find(
                                        (location) =>
                                            location.id === asset.location_id,
                                    )?.name,
                                ]
                                    .filter(Boolean)
                                    .join(' · '),
                                badge: asset.status === 'retired' && (
                                    <Badge variant="secondary">Retired</Badge>
                                ),
                            }))}
                        />
                    ) : (
                        <EmptyState
                            title="No assets recorded"
                            description={`Track the devices and equipment you look after for ${customer.name}.`}
                            action={
                                <AddButton
                                    href={createAsset(customer.id).url}
                                    label="Add asset"
                                />
                            }
                        />
                    )}
                </DetailSection>

                <DetailSection
                    title="Services"
                    action={
                        services.length > 0 && (
                            <AddButton
                                href={createService(customer.id).url}
                                label="Add service"
                            />
                        )
                    }
                >
                    {services.length > 0 ? (
                        <RecordList
                            items={services.map((service) => ({
                                id: service.id,
                                href: editService(service.id).url,
                                title: service.name,
                                detail:
                                    service.unit_price_in_cents != null
                                        ? `${service.quantity} × ${formatCents(service.unit_price_in_cents)} per ${billingPeriod(service.billing_period_in_months)}`
                                        : `Quantity ${service.quantity}`,
                                badge: service.status !== 'active' && (
                                    <Badge variant="secondary">
                                        {serviceStatusLabels[service.status]}
                                    </Badge>
                                ),
                            }))}
                        />
                    ) : (
                        <EmptyState
                            title="No services yet"
                            description={`List what ${customer.name} gets from you, so billing and support stay in step.`}
                            action={
                                <AddButton
                                    href={createService(customer.id).url}
                                    label="Add service"
                                />
                            }
                        />
                    )}
                </DetailSection>
            </div>
        </>
    );
}
