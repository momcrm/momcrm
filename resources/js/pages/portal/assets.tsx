import { Head } from '@inertiajs/react';
import { HardDrive } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import StatusPill from '@/components/status-pill';
import type { PortalAsset } from '@/types';

/**
 * The customer's equipment that the provider looks after.
 */
export default function PortalAssets({ assets }: { assets: PortalAsset[] }) {
    return (
        <>
            <Head title="Assets" />

            <h1 className="text-xl font-semibold">Assets</h1>

            {assets.length === 0 ? (
                <EmptyState
                    icon={HardDrive}
                    title="No assets"
                    description="The equipment we look after for you shows here."
                />
            ) : (
                <div className="overflow-x-auto rounded-lg border bg-card">
                    <table className="w-full text-sm">
                        <thead className="border-b text-left text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2.5 font-medium">
                                    Name
                                </th>
                                <th className="px-4 py-2.5 font-medium">
                                    Serial number
                                </th>
                                <th className="px-4 py-2.5 font-medium">
                                    Location
                                </th>
                                <th className="px-4 py-2.5" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {assets.map((asset) => (
                                <tr key={asset.id}>
                                    <td className="px-4 py-2.5 font-medium">
                                        {asset.name ?? 'Unnamed'}
                                    </td>
                                    <td className="px-4 py-2.5 font-mono text-xs">
                                        {asset.serial_number ?? '—'}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        {asset.location_name ?? '—'}
                                    </td>
                                    <td className="px-4 py-2.5 text-right">
                                        <StatusPill
                                            tone={
                                                asset.is_active
                                                    ? 'success'
                                                    : 'neutral'
                                            }
                                        >
                                            {asset.status}
                                        </StatusPill>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
}
