import { Head } from '@inertiajs/react';
import LinkedRecords from '@/components/custom-fields/linked-records';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { linked } from '@/routes/services';
import type { LinkedRecordGroup, ServiceWorkspace } from '@/types';

export default function ServiceLinkedRecords({
    workspace,
    linkedRecords,
}: {
    workspace: ServiceWorkspace;
    linkedRecords: LinkedRecordGroup[];
}) {
    const { service } = workspace;
    useServiceBreadcrumbs(workspace, {
        title: 'Linked',
        href: linked(service.id),
    });

    return (
        <>
            <Head title={`${service.name} · Linked`} />
            <TabHeader
                title="Linked records"
                description="Tickets, assets, contracts and other records that point at this service."
            />
            {linkedRecords.length > 0 ? (
                <LinkedRecords groups={linkedRecords} />
            ) : (
                <EmptyState
                    title="Nothing linked yet"
                    description="Add a field that links to services, such as a ticket's Service, under Settings → Objects."
                />
            )}
        </>
    );
}
