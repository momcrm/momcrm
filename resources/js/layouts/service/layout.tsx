import { Link, setLayoutProps, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    History,
    LayoutDashboard,
    Link2,
    Receipt,
    Settings,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { serviceLifecycleLabels } from '@/components/services/service-detail';
import SoftBadge from '@/components/soft-badge';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import {
    index as customersIndex,
    services as customerServices,
    show as showCustomer,
} from '@/routes/customers';
import { activity, edit, invoices, linked, show } from '@/routes/services';
import type { ServiceWorkspace } from '@/types';

type SectionLink = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
    count?: number;
    exact?: boolean;
};

function links(workspace: ServiceWorkspace): SectionLink[] {
    const id = workspace.service.id;

    return [
        {
            title: 'Overview',
            href: show(id),
            icon: LayoutDashboard,
            exact: true,
        },
        {
            title: 'Linked',
            href: linked(id),
            icon: Link2,
            count: workspace.counts.linked,
        },
        {
            title: 'Invoices',
            href: invoices(id),
            icon: Receipt,
            count: workspace.counts.invoices,
        },
        { title: 'Activity', href: activity(id), icon: History },
        { title: 'Settings', href: edit(id), icon: Settings },
    ];
}

/**
 * Names the page in the breadcrumbs as Customers › customer › Services ›
 * service › section. The overview passes no section.
 */
export function useServiceBreadcrumbs(
    workspace: ServiceWorkspace,
    section?: { title: string; href: NonNullable<InertiaLinkProps['href']> },
) {
    const { service, customer } = workspace;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Services', href: customerServices(customer.id) },
            { title: service.name, href: show(service.id) },
            ...(section ? [section] : []),
        ],
    });
}

function ServiceHeader({ workspace }: { workspace: ServiceWorkspace }) {
    const { service, customer } = workspace;

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <h1 className="truncate text-[19px] leading-tight font-semibold tracking-[-0.018em]">
                {service.name}
            </h1>
            <div className="flex flex-wrap items-center gap-2 text-[12.5px] text-soft-foreground">
                {service.short_name && (
                    <span className="font-mono">{service.short_name}</span>
                )}
                <Link
                    href={showCustomer(customer.id)}
                    className="hover:text-foreground"
                >
                    {customer.name}
                </Link>
                <SoftBadge
                    tone={
                        service.lifecycle === 'active'
                            ? 'success'
                            : service.lifecycle === 'inactive'
                              ? 'warning'
                              : 'neutral'
                    }
                >
                    {serviceLifecycleLabels[service.lifecycle]}
                </SoftBadge>
                {service.auto_renew && (
                    <SoftBadge tone="info">Auto-renew</SoftBadge>
                )}
            </div>
        </div>
    );
}

/**
 * A service on its own page, as Amphora opens a company service: its name
 * and badges above a nav of sections on the left, the open section beside
 * it. It stays mounted while moving between sections.
 */
export default function ServiceLayout({ children }: PropsWithChildren) {
    const { workspace } = usePage<{ workspace: ServiceWorkspace }>().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
            <ServiceHeader workspace={workspace} />

            <div className="flex flex-col gap-6 md:flex-row md:gap-8">
                <aside className="w-full shrink-0 md:w-48">
                    <nav
                        aria-label={`${workspace.service.name} sections`}
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
