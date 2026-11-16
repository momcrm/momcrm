import { Head } from '@inertiajs/react';
import RecordList from '@/components/customers/record-list';
import { AddLink, TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import { LabelChips } from '@/components/labels';
import { Badge } from '@/components/ui/badge';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { create, edit } from '@/routes/assets';
import { assets as assetsTab } from '@/routes/customers';
import type { Asset, CustomerWorkspace, Location } from '@/types';

export default function CustomerAssets({
    workspace,
    assets,
    locations,
}: {
    workspace: CustomerWorkspace;
    assets: Asset[];
    locations: Location[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Assets',
        href: assetsTab(customer.id),
    });
    const add = <AddLink href={create(customer.id).url} label="Add asset" />;

    return (
        <>
            <Head title={`${customer.name} · Assets`} />
            <TabHeader
                title="Assets"
                description="The devices and equipment you look after."
                action={assets.length > 0 && add}
            />
            {assets.length > 0 ? (
                <RecordList
                    items={assets.map((asset) => ({
                        id: asset.id,
                        href: edit(asset.id).url,
                        title: asset.name,
                        detail: [
                            asset.serial_number,
                            locations.find(
                                (location) => location.id === asset.location_id,
                            )?.name,
                        ]
                            .filter(Boolean)
                            .join(' · '),
                        badge: (
                            <span className="flex items-center gap-1.5">
                                <LabelChips
                                    labels={asset.labels ?? []}
                                    className="hidden flex-nowrap sm:flex"
                                />
                                {asset.status === 'retired' && (
                                    <Badge variant="secondary">Retired</Badge>
                                )}
                            </span>
                        ),
                    }))}
                />
            ) : (
                <EmptyState
                    title="No assets recorded"
                    description={`Track the devices and equipment you look after for ${customer.name}.`}
                    action={add}
                />
            )}
        </>
    );
}
