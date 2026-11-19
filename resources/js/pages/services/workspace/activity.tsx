import { Head } from '@inertiajs/react';
import { ActivityList, AuditTrail } from '@/components/activity-log';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { activity } from '@/routes/services';
import type {
    ActivityItem,
    AuditLogItem,
    Paginated,
    ServiceWorkspace,
} from '@/types';

export default function ServiceActivity({
    workspace,
    activities,
    auditLogs,
}: {
    workspace: ServiceWorkspace;
    activities: Paginated<ActivityItem>;
    auditLogs: Paginated<AuditLogItem>;
}) {
    const { service } = workspace;
    useServiceBreadcrumbs(workspace, {
        title: 'Activity',
        href: activity(service.id),
    });

    return (
        <>
            <Head title={`${service.name} · Activity`} />
            <div className="space-y-8">
                <ActivityList
                    activities={activities}
                    description="Turned on or off, archived, restored and moved between rates."
                />
                <AuditTrail
                    auditLogs={auditLogs}
                    description="Every change to the service's fields."
                />
            </div>
        </>
    );
}
