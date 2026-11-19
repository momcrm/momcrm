import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import RecordList from '@/components/customers/record-list';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import AddServiceDialog from '@/components/services/add-service-dialog';
import {
    serviceDetail,
    serviceLifecycleLabels,
} from '@/components/services/service-detail';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { services as servicesTab } from '@/routes/customers';
import { show } from '@/routes/services';
import type { CustomerWorkspace, Service, ServiceFormOptions } from '@/types';

export default function CustomerServices({
    workspace,
    services,
    archivedCount,
    showsArchived,
    serviceForm,
}: {
    workspace: CustomerWorkspace;
    services: Service[];
    archivedCount: number;
    showsArchived: boolean;
    serviceForm: ServiceFormOptions;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Services',
        href: servicesTab(customer.id),
    });
    const [isAdding, setIsAdding] = useState(false);
    const add = (
        <Button variant="outline" size="sm" onClick={() => setIsAdding(true)}>
            <Plus />
            Add service
        </Button>
    );
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
                            href: show(service.id).url,
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
            <AddServiceDialog
                customer={{ id: customer.id, name: customer.name }}
                options={serviceForm}
                open={isAdding}
                onOpenChange={setIsAdding}
            />
        </>
    );
}
