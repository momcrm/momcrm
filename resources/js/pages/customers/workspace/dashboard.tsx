import { Head, Link } from '@inertiajs/react';
import LinkedRecords from '@/components/custom-fields/linked-records';
import { useCustomFields } from '@/components/custom-fields/custom-field-input';
import {
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';
import InvoiceDialog, {
    useInvoiceDialog,
} from '@/components/invoices/invoice-dialog';
import { serviceDetail } from '@/components/services/service-detail';
import {
    DueChip,
    PriorityGlyph,
    StatusGlyph,
    usePriority,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import RecordPaymentButton from '@/components/payments/record-payment-button';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import {
    assets,
    contacts,
    contracts,
    files,
    invoices,
    locations,
    projects,
    secrets,
    services,
    tickets,
} from '@/routes/customers';
import { show as showService } from '@/routes/services';
import { show as showTicket } from '@/routes/tickets';
import type {
    ContractItem,
    CustomerMoney,
    CustomerWorkspace,
    InvoiceListItem,
    LinkedRecordGroup,
    Service,
    TicketListItem,
} from '@/types';

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(day: string | null): string {
    return day ? dayFormatter.format(new Date(`${day}T00:00:00`)) : '—';
}

/**
 * What the customer owes as one strip divided by hairlines, as staff-vue's
 * company dashboard shows it: everything owed, what isn't due yet, what is
 * overdue (flagged when it isn't zero) and the credit they have.
 */
function MoneyStrip({
    money,
    currency,
}: {
    money: CustomerMoney;
    currency: string;
}) {
    const figures = [
        { label: 'Balance', value: money.balance_in_cents, alarm: false },
        { label: 'Current', value: money.current_in_cents, alarm: false },
        {
            label: 'Overdue',
            value: money.overdue_in_cents,
            alarm: money.overdue_in_cents > 0,
        },
        { label: 'Credit', value: money.credit_in_cents, alarm: false },
    ];

    return (
        <section
            aria-label="What they owe"
            className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-4"
        >
            {figures.map((figure) => (
                <div
                    key={figure.label}
                    className="flex flex-col gap-1 border-b p-4 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
                >
                    <span
                        className={cn(
                            'flex items-center gap-1.5 text-xs text-muted-foreground',
                            figure.alarm && 'text-destructive',
                        )}
                    >
                        {figure.alarm && <AlertTriangle className="size-3.5" />}
                        {figure.label}
                    </span>
                    <span
                        className={cn(
                            'text-2xl font-semibold tabular-nums',
                            figure.alarm && 'text-destructive',
                        )}
                    >
                        {formatCents(figure.value, currency)}
                    </span>
                </div>
            ))}
        </section>
    );
}

function Card({
    title,
    viewAll,
    empty,
    isEmpty,
    className,
    children,
}: {
    title: string;
    viewAll?: string;
    empty?: string;
    isEmpty?: boolean;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'flex flex-col rounded-xl border bg-card p-4 shadow-xs',
                className,
            )}
        >
            <div className="mb-3 flex items-center justify-between gap-2">
                <h2 className="text-sm font-medium">{title}</h2>
                {viewAll && (
                    <Link
                        href={viewAll}
                        className="text-xs text-muted-foreground hover:text-foreground"
                    >
                        View all
                    </Link>
                )}
            </div>
            {isEmpty ? (
                <p className="text-sm text-muted-foreground">{empty}</p>
            ) : (
                children
            )}
        </section>
    );
}

function TicketRow({ ticket }: { ticket: TicketListItem }) {
    const status = useTicketStatus(ticket.status_id);
    const priority = usePriority(ticket.priority_id);

    return (
        <li>
            <Link
                href={showTicket(ticket.id)}
                className="-mx-2 flex h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-accent/50"
            >
                <StatusGlyph status={status} />
                <span className="font-mono text-xs text-subtle-foreground">
                    #{ticket.number}
                </span>
                <span className="min-w-0 flex-1 truncate">{ticket.title}</span>
                <DueChip dueOn={ticket.due_on} isClosed={false} />
                <PriorityGlyph priority={priority} />
            </Link>
        </li>
    );
}

function InvoiceRow({
    invoice,
    onOpen,
}: {
    invoice: InvoiceListItem;
    onOpen: (id: string) => void;
}) {
    return (
        <li>
            <button
                type="button"
                onClick={() => onOpen(invoice.id)}
                className="-mx-2 flex h-9 w-[calc(100%+1rem)] items-center gap-2.5 rounded-md px-2 text-left text-sm hover:bg-accent/50"
            >
                <span className="font-mono text-xs text-subtle-foreground">
                    #{invoice.number}
                </span>
                <span className="min-w-0 flex-1 truncate text-muted-foreground">
                    Issued {formatDate(invoice.issued_on)}
                </span>
                <span className="font-medium tabular-nums">
                    {formatCents(invoice.total_in_cents, invoice.currency)}
                </span>
            </button>
        </li>
    );
}

function ServiceRow({ service }: { service: Service }) {
    return (
        <li>
            <Link
                href={showService(service.id)}
                className="-mx-2 flex h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-accent/50"
            >
                <span className="min-w-0 flex-1 truncate">
                    {service.name}
                    <span className="ml-2 text-xs text-muted-foreground">
                        {serviceDetail(service)}
                    </span>
                </span>
                <span className="text-xs text-warning tabular-nums">
                    Expires {formatDate(service.ends_on)}
                </span>
            </Link>
        </li>
    );
}

function ContractRow({
    contract,
    href,
}: {
    contract: ContractItem;
    href: string;
}) {
    return (
        <li>
            <Link
                href={href}
                className="-mx-2 flex h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-accent/50"
            >
                <span className="min-w-0 flex-1 truncate">
                    {contract.title}
                    <span className="ml-2 text-xs text-muted-foreground uppercase">
                        {contract.type}
                    </span>
                </span>
                <span className="text-xs text-warning tabular-nums">
                    Ends {formatDate(contract.ends_on)}
                </span>
            </Link>
        </li>
    );
}

export default function CustomerDashboard({
    workspace,
    money,
    openTickets,
    draftInvoices,
    expiringServices,
    expiringContracts,
    linkedRecords,
}: {
    workspace: CustomerWorkspace;
    money: CustomerMoney;
    openTickets: TicketListItem[];
    draftInvoices: InvoiceListItem[];
    expiringServices: Service[];
    expiringContracts: ContractItem[];
    /** What points at this customer through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
}) {
    const { customer, counts } = workspace;
    const id = customer.id;
    const { openInvoice } = useInvoiceDialog();
    useWorkspaceBreadcrumbs(customer);

    const customFields = useCustomFields('customer');
    const details: { label: string; value: ReactNode }[] = [
        { label: 'Email', value: customer.email },
        { label: 'Phone', value: customer.phone },
        { label: 'Website', value: customer.website },
        {
            label: customer.tax_id_label ?? 'Tax ID',
            value: customer.tax_id,
        },
        ...customFields
            .filter((field) =>
                hasCustomFieldValue(field, customer.custom_fields[field.id]),
            )
            .map((field) => ({
                label: field.name,
                value: (
                    <CustomFieldValueView
                        field={field}
                        value={customer.custom_fields[field.id]}
                    />
                ),
            })),
    ].filter((detail) => detail.value);

    const tiles = [
        { label: 'Open tickets', count: counts.tickets, href: tickets(id) },
        { label: 'Projects', count: counts.projects, href: projects(id) },
        { label: 'Contacts', count: counts.contacts, href: contacts(id) },
        { label: 'Locations', count: counts.locations, href: locations(id) },
        { label: 'Assets', count: counts.assets, href: assets(id) },
        { label: 'Services', count: counts.services, href: services(id) },
        { label: 'Contracts', count: counts.contracts, href: contracts(id) },
        { label: 'Files', count: counts.files, href: files(id) },
        { label: 'Secrets', count: counts.secrets, href: secrets(id) },
        { label: 'Invoices', count: counts.invoices, href: invoices(id) },
    ];

    return (
        <>
            <Head title={customer.name} />

            <div className="@container flex flex-col gap-4">
                <div className="flex flex-col gap-2">
                    <div className="flex justify-end">
                        <RecordPaymentButton customerId={customer.id} />
                    </div>
                    <MoneyStrip money={money} currency={customer.currency} />
                </div>

                <div className="grid min-w-0 gap-4 @3xl:grid-cols-2">
                    <Card
                        title="Open tickets"
                        viewAll={tickets(id).url}
                        isEmpty={openTickets.length === 0}
                        empty="Nothing open. All caught up."
                    >
                        <ul>
                            {openTickets.map((ticket) => (
                                <TicketRow key={ticket.id} ticket={ticket} />
                            ))}
                        </ul>
                    </Card>

                    <Card
                        title="Draft invoices"
                        viewAll={
                            invoices(id, { query: { status: 'draft' } }).url
                        }
                        isEmpty={draftInvoices.length === 0}
                        empty="No invoices waiting to be sent."
                    >
                        <ul>
                            {draftInvoices.map((invoice) => (
                                <InvoiceRow
                                    key={invoice.id}
                                    invoice={invoice}
                                    onOpen={openInvoice}
                                />
                            ))}
                        </ul>
                        <InvoiceDialog />
                    </Card>

                    <Card
                        title="Ending soon"
                        isEmpty={
                            expiringServices.length === 0 &&
                            expiringContracts.length === 0
                        }
                        empty="No active services or contracts end in the next 30 days."
                    >
                        <ul>
                            {expiringContracts.map((contract) => (
                                <ContractRow
                                    key={contract.id}
                                    contract={contract}
                                    href={contracts(id).url}
                                />
                            ))}
                            {expiringServices.map((service) => (
                                <ServiceRow
                                    key={service.id}
                                    service={service}
                                />
                            ))}
                        </ul>
                    </Card>

                    <Card
                        title="Details"
                        isEmpty={details.length === 0 && !customer.notes}
                        empty="No contact details yet. Add them in Settings."
                    >
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            {details.map((detail) => (
                                <div key={detail.label} className="min-w-0">
                                    <dt className="text-xs text-muted-foreground">
                                        {detail.label}
                                    </dt>
                                    <dd className="truncate">{detail.value}</dd>
                                </div>
                            ))}
                            {customer.notes && (
                                <div className="sm:col-span-2">
                                    <dt className="text-xs text-muted-foreground">
                                        Notes
                                    </dt>
                                    <dd className="whitespace-pre-line">
                                        {customer.notes}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </Card>
                </div>

                <LinkedRecords groups={linkedRecords} />

                <section
                    aria-label="In this account"
                    className="grid grid-cols-2 overflow-hidden rounded-xl border bg-card shadow-xs @lg:grid-cols-4 @3xl:grid-cols-5"
                >
                    {tiles.map((tile) => (
                        <Link
                            key={tile.label}
                            href={tile.href}
                            className="flex flex-col gap-0.5 border-r border-b p-3 hover:bg-accent/50"
                        >
                            <span className="text-lg font-semibold tabular-nums">
                                {tile.count}
                            </span>
                            <span className="truncate text-[11px] text-muted-foreground">
                                {tile.label}
                            </span>
                        </Link>
                    ))}
                </section>
            </div>
        </>
    );
}
