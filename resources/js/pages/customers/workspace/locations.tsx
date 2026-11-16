import { Head } from '@inertiajs/react';
import RecordList from '@/components/customers/record-list';
import { AddLink, TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { locations as locationsTab } from '@/routes/customers';
import { create, edit } from '@/routes/locations';
import type { CustomerWorkspace, Location } from '@/types';

export default function CustomerLocations({
    workspace,
    locations,
}: {
    workspace: CustomerWorkspace;
    locations: Location[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Locations',
        href: locationsTab(customer.id),
    });
    const add = <AddLink href={create(customer.id).url} label="Add location" />;

    return (
        <>
            <Head title={`${customer.name} · Locations`} />
            <TabHeader
                title="Locations"
                description="Offices and sites, so tickets and assets know where they belong."
                action={locations.length > 0 && add}
            />
            {locations.length > 0 ? (
                <RecordList
                    items={locations.map((location) => ({
                        id: location.id,
                        href: edit(location.id).url,
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
                    action={add}
                />
            )}
        </>
    );
}
