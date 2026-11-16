import { Head, Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { ReactNode } from 'react';
import { serviceDetail } from '@/components/services/service-detail';
import {
    DueChip,
    PriorityGlyph,
    StatusGlyph,
    usePriority,
    useTicketStatus,
} from '@/components/tickets/ticket-meta';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import {
    assets,
    contacts,
    invoices,
    locations,
    projects,
    services,
    tickets,
} from '@/routes/customers';
import { show as showInvoice } from '@/routes/invoices';
import { edit as editService } from '@/routes/services';
import { show as showTicket } from '@/routes/tickets';
import type {
    CustomerMoney,
    CustomerWorkspace,
    InvoiceListItem,
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
 * company dashboard shows it: everything owed, what isn't due yet, and what
 * is overdue (flagged when it isn't zero).
 */
function MoneyStrip({ money }: { money: CustomerMoney }) {
    const figures = [
        { label: 'Balance', value: money.balance_in_cents, alarm: false },
        { label: 'Current', value: money.current_in_cents, alarm: false },
        {
            label: 'Overdue',
            value: money.overdue_in_cents,
            alarm: money.overdue_in_cents > 0,
        },
    ];

    return (
        <section
            aria-label="What they owe"
            className="grid overflow-hidden rounded-xl border bg-card shadow-xs sm:grid-cols-3"
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
                        {formatCents(figure.value)}
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

function InvoiceRow({ invoice }: { invoice: InvoiceListItem }) {
    return (
        <li>
            <Link
                href={showInvoice(invoice.id)}
                className="-mx-2 flex h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-accent/50"
            >
                <span className="font-mono text-xs text-subtle-foreground">
                    #{invoice.number}
                </span>
                <span className="min-w-0 flex-1 truncate text-muted-foreground">
                    Issued {formatDate(invoice.issued_on)}
                </span>
                <span className="font-medium tabular-nums">
                    {formatCents(invoice.total_in_cents)}
                </span>
            </Link>
        </li>
    );
}

function ServiceRow({ service }: { service: Service }) {
    return (
        <li>
            <Link
                href={editService(service.id)}
                className="-mx-2 flex h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-accent/50"
            >
                <span className="min-w-0 flex-1 truncate">
                    {service.name}
                    <span className="ml-2 text-xs text-muted-foreground">
                        {serviceDetail(service)}
                    </span>
                </span>
                <span className="text-xs text-warning tabular-nums">
                    Ends {formatDate(service.ends_on)}
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
}: {
    workspace: CustomerWorkspace;
    money: CustomerMoney;
    openTickets: TicketListItem[];
    draftInvoices: InvoiceListItem[];
    expiringServices: Service[];
}) {
    const { customer, counts } = workspace;
    const id = customer.id;
    useWorkspaceBreadcrumbs(customer);

    const details = [
        { label: 'Email', value: customer.email },
        { label: 'Phone', value: customer.phone },
        { label: 'Website', value: customer.website },
        { label: 'ABN', value: customer.abn },
    ].filter((detail) => detail.value);

    const tiles = [
        { label: 'Open tickets', count: counts.tickets, href: tickets(id) },
        { label: 'Projects', count: counts.projects, href: projects(id) },
        { label: 'Contacts', count: counts.contacts, href: contacts(id) },
        { label: 'Locations', count: counts.locations, href: locations(id) },
        { label: 'Assets', count: counts.assets, href: assets(id) },
        { label: 'Services', count: counts.services, href: services(id) },
        { label: 'Invoices', count: counts.invoices, href: invoices(id) },
    ];

    return (
        <>
            <Head title={customer.name} />

            <div className="@container flex flex-col gap-4">
                <MoneyStrip money={money} />

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
                                />
                            ))}
                        </ul>
                    </Card>

                    <Card
                        title="Services ending soon"
                        viewAll={services(id).url}
                        isEmpty={expiringServices.length === 0}
                        empty="No active services end in the next 30 days."
                    >
                        <ul>
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

                <section
                    aria-label="In this account"
                    className="grid grid-cols-2 overflow-hidden rounded-xl border bg-card shadow-xs @lg:grid-cols-4 @3xl:grid-cols-7"
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
