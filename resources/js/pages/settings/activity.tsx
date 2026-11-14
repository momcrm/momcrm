import { Head } from '@inertiajs/react';
import { ActivityList, AuditTrail } from '@/components/activity-log';
import { index } from '@/routes/activity';
import type { ActivityItem, AuditLogItem, Paginated } from '@/types';

export default function Activity({
    activities,
    auditLogs,
}: {
    activities: Paginated<ActivityItem>;
    auditLogs: Paginated<AuditLogItem>;
}) {
    return (
        <>
            <Head title="Account activity" />

            <h1 className="sr-only">Account activity</h1>

            <ActivityList
                activities={activities}
                description="Sign-ins and security changes on your account"
            />

            <AuditTrail
                auditLogs={auditLogs}
                description="Changes to your account details"
            />
        </>
    );
}

Activity.layout = {
    breadcrumbs: [
        {
            title: 'Account activity',
            href: index(),
        },
    ],
};
