import { Head } from '@inertiajs/react';
import { ActivityList, AuditTrail } from '@/components/activity-log';
import { index } from '@/routes/team-activity';
import type { ActivityItem, AuditLogItem, Paginated, Team } from '@/types';

export default function TeamActivity({
    team,
    activities,
    auditLogs,
}: {
    team: Team;
    activities: Paginated<ActivityItem>;
    auditLogs: Paginated<AuditLogItem>;
}) {
    return (
        <>
            <Head title="Team activity" />

            <h1 className="sr-only">Team activity</h1>

            <ActivityList
                activities={activities}
                description={`What members did in ${team.name}`}
            />

            <AuditTrail
                auditLogs={auditLogs}
                description={`Changes to ${team.name} and its members`}
            />
        </>
    );
}

TeamActivity.layout = {
    breadcrumbs: [
        {
            title: 'Team activity',
            href: index(),
        },
    ],
};
