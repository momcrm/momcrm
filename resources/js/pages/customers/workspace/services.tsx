import { Head, Link } from '@inertiajs/react';
import RecordList from '@/components/customers/record-list';
import { AddLink, TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import {
    serviceDetail,
    serviceLifecycleLabels,
} from '@/components/services/service-detail';
import SoftBadge from '@/components/soft-badge';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { services as servicesTab } from '@/routes/customers';
import { create, edit } from '@/routes/services';
import type { CustomerWorkspace, Service } from '@/types';

export default function CustomerServices({
    workspace,
    services,
    archivedCount,
    showsArchived,
}: {
    workspace: CustomerWorkspace;
    services: Service[];
    archivedCount: number;
    showsArchived: boolean;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Services',
        href: servicesTab(customer.id),
    });
    const add = <AddLink href={create(customer.id).url} label="Add service" />;
    const archiveToggle = archivedCount > 0 && (
        <Link
            href={servicesTab(customer.id, {
                query: showsArchived ? {} : { archived: 1 },
            })}
            preserveScroll
            className="text-xs text-muted-foreground hover:text-foreground"
        >
            {showsArchived ? 'Hide archived' : `Show ${archivedCount} archived`}
        </Link>
    );

    return (
        <>
            <Head title={`${customer.name} · Services`} />
            <TabHeader
                title="Services"
                description="What the customer gets from you, so billing and support stay in step."
                action={services.length > 0 && add}
            />
            {services.length > 0 ? (
                <div className="space-y-2">
                    <RecordList
                        items={services.map((service) => ({
                            id: service.id,
                            href: edit(service.id).url,
                            title: service.name,
                            detail: [
                                service.short_name,
                                service.rate?.name,
                                serviceDetail(service),
                            ]
                                .filter(Boolean)
                                .join(' · '),
                            badge: service.lifecycle !== 'active' && (
                                <SoftBadge
                                    tone={
                                        service.lifecycle === 'archived'
                                            ? 'neutral'
                                            : 'warning'
                                    }
                                >
                                    {serviceLifecycleLabels[service.lifecycle]}
                                </SoftBadge>
                            ),
                        }))}
                    />
                    {archiveToggle}
                </div>
            ) : (
                <EmptyState
                    title="No services yet"
                    description={`List what ${customer.name} gets from you, so billing and support stay in step.`}
                    action={
                        <div className="flex flex-col items-center gap-2">
                            {add}
                            {archiveToggle}
                        </div>
                    }
                />
            )}
        </>
    );
}
