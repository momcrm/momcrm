import { Head } from '@inertiajs/react';
import { usePortal } from '@/layouts/portal-layout';

/**
 * The customer portal's home page.
 */
export default function PortalDashboard() {
    const portal = usePortal();

    return (
        <>
            <Head title="Home" />

            <div className="space-y-1">
                <h1 className="text-xl font-semibold">
                    Hi {portal.contact.name.split(' ')[0]}
                </h1>
                <p className="text-sm text-muted-foreground">
                    This is {portal.team.name}'s customer portal for{' '}
                    {portal.customer.name}.
                </p>
            </div>
        </>
    );
}
