import { Head, Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import type { ReactNode } from 'react';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import ProjectCard from '@/components/projects/project-card';
import TicketDrawer from '@/components/tickets/ticket-drawer';
import TicketRow from '@/components/tickets/ticket-row';
import { useTicketActions } from '@/hooks/use-ticket-actions';
import { index } from '@/routes/favorites';
import { show as showCustomer } from '@/routes/customers';
import type {
    Customer,
    ProjectListItem,
    TicketDrawerData,
    TicketListItem,
    TicketPickerOptions,
} from '@/types';

export default function Favorites({
    projects,
    tickets,
    customers,
    favoriteTicketIds,
    options,
    drawer,
}: {
    projects: ProjectListItem[];
    tickets: TicketListItem[];
    customers: Customer[];
    favoriteTicketIds: string[];
    options: TicketPickerOptions;
    drawer: TicketDrawerData | null;
}) {
    const { openTicket } = useTicketActions();
    const isEmpty = projects.length + tickets.length + customers.length === 0;

    return (
        <>
            <Head title="Favorites" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Favorites"
                    description="The projects, tickets and customers you starred."
                />

                {isEmpty ? (
                    <EmptyState
                        title="Nothing starred yet"
                        description="Star a project, ticket or customer to keep it one click away."
                    />
                ) : (
                    <>
                        {projects.length > 0 && (
                            <Section title="Projects" count={projects.length}>
                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    {projects.map((project) => (
                                        <ProjectCard
                                            key={project.id}
                                            project={project}
                                        />
                                    ))}
                                </div>
                            </Section>
                        )}
                        {tickets.length > 0 && (
                            <Section title="Tickets" count={tickets.length}>
                                <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                                    {tickets.map((ticket) => (
                                        <li key={ticket.id}>
                                            <TicketRow
                                                ticket={ticket}
                                                isFavorite={favoriteTicketIds.includes(
                                                    ticket.id,
                                                )}
                                                onOpen={() =>
                                                    openTicket(ticket.id)
                                                }
                                            />
                                        </li>
                                    ))}
                                </ul>
                            </Section>
                        )}
                        {customers.length > 0 && (
                            <Section title="Customers" count={customers.length}>
                                <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                                    {customers.map((customer) => (
                                        <li key={customer.id}>
                                            <Link
                                                href={showCustomer(customer.id)}
                                                className="flex h-10 items-center gap-3 px-3 text-sm hover:bg-accent/50"
                                            >
                                                <Building2 className="size-4 text-subtle-foreground" />
                                                <span className="font-medium">
                                                    {customer.name}
                                                </span>
                                                <span className="ml-auto truncate text-xs text-muted-foreground">
                                                    {customer.email}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </Section>
                        )}
                    </>
                )}
            </div>

            <TicketDrawer ticket={drawer} options={options} />
        </>
    );
}

function Section({
    title,
    count,
    children,
}: {
    title: string;
    count: number;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <h2 className="flex items-center gap-2 text-sm font-medium">
                {title}
                <span className="text-xs font-normal text-subtle-foreground">
                    {count}
                </span>
            </h2>
            {children}
        </section>
    );
}

Favorites.layout = {
    breadcrumbs: [{ title: 'Favorites', href: index() }],
};
