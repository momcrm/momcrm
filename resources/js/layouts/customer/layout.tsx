import { Link, setLayoutProps, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    Banknote,
    BookOpen,
    Boxes,
    FilePlus2,
    FileSignature,
    FileStack,
    KeyRound,
    FolderKanban,
    LayoutDashboard,
    MapPin,
    Receipt,
    ScrollText,
    Server,
    Settings,
    Ticket,
    UsersRound,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import {
    BillingTypeBadge,
    PrefixCode,
} from '@/components/customers/customer-meta';
import SoftBadge from '@/components/soft-badge';
import { ChoiceTag, useChoice } from '@/components/tickets/ticket-meta';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import {
    articles,
    assets,
    billing,
    contacts,
    contracts,
    edit,
    files,
    index,
    invoices,
    locations,
    payments,
    projects,
    secrets,
    services,
    show,
    statement,
    tickets,
} from '@/routes/customers';
import type { Customer, CustomerWorkspace } from '@/types';

type WorkspaceLink = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
    count?: number;
    /** Active only on its own URL, not on the tabs below it. */
    exact?: boolean;
};

function links(workspace: CustomerWorkspace): WorkspaceLink[] {
    const id = workspace.customer.id;
    const { counts } = workspace;

    return [
        {
            title: 'Dashboard',
            href: show(id),
            icon: LayoutDashboard,
            exact: true,
        },
        {
            title: 'Tickets',
            href: tickets(id),
            icon: Ticket,
            count: counts.tickets,
        },
        {
            title: 'Projects',
            href: projects(id),
            icon: FolderKanban,
            count: counts.projects,
        },
        {
            title: 'Contacts',
            href: contacts(id),
            icon: UsersRound,
            count: counts.contacts,
        },
        {
            title: 'Locations',
            href: locations(id),
            icon: MapPin,
            count: counts.locations,
        },
        {
            title: 'Assets',
            href: assets(id),
            icon: Boxes,
            count: counts.assets,
        },
        {
            title: 'Services',
            href: services(id),
            icon: Server,
            count: counts.services,
        },
        {
            title: 'Contracts',
            href: contracts(id),
            icon: FileSignature,
            count: counts.contracts,
        },
        {
            title: 'Files',
            href: files(id),
            icon: FileStack,
            count: counts.files,
        },
        {
            title: 'Secrets',
            href: secrets(id),
            icon: KeyRound,
            count: counts.secrets,
        },
        {
            title: 'Articles',
            href: articles(id),
            icon: BookOpen,
            count: counts.articles,
        },
        {
            title: 'Invoices',
            href: invoices(id),
            icon: Receipt,
            count: counts.invoices,
        },
        {
            title: 'Billing',
            href: billing({ customer: id }),
            icon: FilePlus2,
            count: counts.waiting_lines,
        },
        {
            title: 'Payments',
            href: payments(id),
            icon: Banknote,
            count: counts.payments,
        },
        { title: 'Statement', href: statement(id), icon: ScrollText },
        { title: 'Settings', href: edit(id), icon: Settings },
    ];
}

/**
 * Names the page in the breadcrumbs as Customers › customer › tab. The
 * dashboard passes no tab.
 */
export function useWorkspaceBreadcrumbs(
    customer: Customer,
    tab?: { title: string; href: NonNullable<InertiaLinkProps['href']> },
) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: index() },
            { title: customer.name, href: show(customer.id) },
            ...(tab ? [tab] : []),
        ],
    });
}

function WorkspaceHeader({ customer }: { customer: Customer }) {
    const type = useChoice('customer_type', customer.type_id);

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <div className="flex min-w-0 items-center gap-2">
                <PrefixCode prefix={customer.prefix} />
                <h1 className="truncate text-[19px] leading-tight font-semibold tracking-[-0.018em]">
                    {customer.name}
                </h1>
            </div>
            <div className="flex flex-wrap items-center gap-2 text-[12.5px] text-soft-foreground">
                <span className="font-mono">#{customer.number}</span>
                {type && <ChoiceTag choice={type} />}
                <BillingTypeBadge billingType={customer.billing_type} />
                {customer.status === 'inactive' && (
                    <SoftBadge tone="neutral">Inactive</SoftBadge>
                )}
                {customer.is_sample && (
                    <SoftBadge tone="neutral">Sample</SoftBadge>
                )}
            </div>
        </div>
    );
}

/**
 * A customer opened in its own workspace, as staff-vue opens a company: its
 * name and badges above a nav of the customer's records on the left, with
 * the open tab beside it. It stays mounted while moving between tabs.
 */
export default function CustomerLayout({ children }: PropsWithChildren) {
    const { workspace } = usePage<{ workspace: CustomerWorkspace }>().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
            <WorkspaceHeader customer={workspace.customer} />

            <div className="flex flex-col gap-6 md:flex-row md:gap-8">
                <aside className="w-full shrink-0 md:w-48">
                    <nav
                        aria-label={`${workspace.customer.name} sections`}
                        className="-mx-1 flex gap-1 overflow-x-auto px-1 md:mx-0 md:flex-col md:gap-0.5 md:overflow-visible md:px-0"
                    >
                        {links(workspace).map((link) => {
                            const isActive = link.exact
                                ? isCurrentUrl(link.href)
                                : isCurrentOrParentUrl(link.href);

                            return (
                                <Link
                                    key={toUrl(link.href)}
                                    href={link.href}
                                    preserveScroll
                                    aria-current={isActive ? 'page' : undefined}
                                    className={cn(
                                        'flex h-8 shrink-0 items-center gap-2.5 rounded-md px-2 text-sm whitespace-nowrap text-muted-foreground hover:bg-accent hover:text-foreground',
                                        isActive &&
                                            'bg-card font-medium text-foreground shadow-card',
                                    )}
                                >
                                    <link.icon className="size-4" />
                                    <span className="flex-1">{link.title}</span>
                                    {link.count !== undefined &&
                                        link.count > 0 && (
                                            <span className="text-xs text-subtle-foreground tabular-nums">
                                                {link.count}
                                            </span>
                                        )}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="min-w-0 flex-1">{children}</div>
            </div>
        </div>
    );
}
