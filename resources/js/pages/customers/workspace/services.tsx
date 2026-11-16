import { Head } from '@inertiajs/react';
import RecordList from '@/components/customers/record-list';
import { AddLink, TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { serviceDetail } from '@/components/services/service-detail';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { services as servicesTab } from '@/routes/customers';
import { create, edit } from '@/routes/services';
import type { CustomerWorkspace, Service } from '@/types';

const serviceStatusLabels: Record<Service['status'], string> = {
    active: 'Active',
    pending: 'Pending',
    cancelled: 'Cancelled',
};

export default function CustomerServices({
    workspace,
    services,
}: {
    workspace: CustomerWorkspace;
    services: Service[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Services',
        href: servicesTab(customer.id),
    });
    const add = <AddLink href={create(customer.id).url} label="Add service" />;

    return (
        <>
            <Head title={`${customer.name} · Services`} />
            <TabHeader
                title="Services"
                description="What the customer gets from you, so billing and support stay in step."
                action={services.length > 0 && add}
            />
            {services.length > 0 ? (
                <RecordList
                    items={services.map((service) => ({
                        id: service.id,
                        href: edit(service.id).url,
                        title: service.name,
                        detail: serviceDetail(service),
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
                    action={add}
                />
            )}
        </>
    );
}
