import Heading from '@/components/heading';
import Pager from '@/components/pager';
import { Badge } from '@/components/ui/badge';
import type { ActivityItem, AuditLogItem, Paginated } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '';
}

function Meta({
    actor,
    ipAddress,
    createdAt,
}: {
    actor: string | null;
    ipAddress: string | null;
    createdAt: string | null;
}) {
    return (
        <p className="text-xs text-muted-foreground">
            {actor ?? 'System'}
            {ipAddress && ` · ${ipAddress}`}
            {createdAt && ` · ${formatDate(createdAt)}`}
        </p>
    );
}

function detailsText(details: ActivityItem['details']): string | null {
    if (!details) {
        return null;
    }

    if (typeof details.from === 'string' && typeof details.to === 'string') {
        return `${details.from} → ${details.to}`;
    }

    if (Array.isArray(details.fields)) {
        return details.fields.join(', ');
    }

    return null;
}

export function ActivityList({
    activities,
    description,
}: {
    activities: Paginated<ActivityItem>;
    description: string;
}) {
    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Activity"
                description={description}
            />

            {activities.data.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No activity yet.
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {activities.data.map((activity) => (
                        <li
                            key={activity.id}
                            className="space-y-1 px-4 py-3"
                            data-test="activity-item"
                        >
                            <p className="text-sm font-medium">
                                {activity.label}
                                {detailsText(activity.details) && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        — {detailsText(activity.details)}
                                    </span>
                                )}
                            </p>
                            <Meta
                                actor={activity.actor}
                                ipAddress={activity.ip_address}
                                createdAt={activity.created_at}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <Pager page={activities} only="activities" />
        </section>
    );
}

const eventVariant = {
    created: 'secondary',
    updated: 'outline',
    deleted: 'destructive',
    viewed: 'outline',
} as const;

export function AuditTrail({
    auditLogs,
    description,
}: {
    auditLogs: Paginated<AuditLogItem>;
    description: string;
}) {
    return (
        <section className="space-y-4">
            <Heading
                variant="small"
                title="Audit trail"
                description={description}
            />

            {auditLogs.data.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No changes recorded yet.
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {auditLogs.data.map((auditLog) => (
                        <li
                            key={auditLog.id}
                            className="space-y-2 px-4 py-3"
                            data-test="audit-item"
                        >
                            <div className="flex items-center gap-2">
                                <Badge variant={eventVariant[auditLog.event]}>
                                    {auditLog.event}
                                </Badge>
                                <span className="text-sm font-medium">
                                    {auditLog.subject.replace('_', ' ')}
                                </span>
                            </div>
                            {auditLog.changes.length > 0 && (
                                <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                                    {auditLog.changes.map((change) => (
                                        <div
                                            key={change.field}
                                            className="contents"
                                        >
                                            <dt className="text-muted-foreground">
                                                {change.field}
                                            </dt>
                                            <dd className="break-all">
                                                {auditLog.event === 'updated'
                                                    ? `${change.old ?? '—'} → ${change.new ?? '—'}`
                                                    : (change.new ??
                                                      change.old ??
                                                      '—')}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                            <Meta
                                actor={auditLog.actor}
                                ipAddress={auditLog.ip_address}
                                createdAt={auditLog.created_at}
                            />
                        </li>
                    ))}
                </ul>
            )}

            <Pager page={auditLogs} only="auditLogs" />
        </section>
    );
}
