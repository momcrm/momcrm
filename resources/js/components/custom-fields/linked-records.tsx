import { Link } from '@inertiajs/react';
import type { LinkedRecordGroup } from '@/types';

/**
 * What points at a record through record-link custom fields, grouped by the
 * field that links it: "Tickets · Vehicle" lists the tickets whose Vehicle
 * is this record. Renders nothing when nothing links here.
 */
export default function LinkedRecords({
    groups,
}: {
    groups: LinkedRecordGroup[];
}) {
    if (groups.length === 0) {
        return null;
    }

    return (
        <section className="rounded-lg border bg-card shadow-xs">
            <h2 className="border-b px-4 py-2.5 text-sm font-medium">
                Linked records
            </h2>
            <div className="divide-y">
                {groups.map((group) => (
                    <div key={group.key} className="px-4 py-3">
                        <h3 className="flex items-baseline gap-1.5 text-xs text-muted-foreground">
                            <span className="font-medium text-foreground">
                                {group.source}
                            </span>
                            <span>· {group.field}</span>
                            <span className="ml-auto tabular-nums">
                                {group.count}
                            </span>
                        </h3>
                        <ul className="mt-1.5 space-y-0.5 text-sm">
                            {group.records.map((record) => (
                                <li key={record.id}>
                                    <Link
                                        href={record.url}
                                        className="block truncate rounded-md px-2 py-1 hover:bg-accent/60"
                                    >
                                        {record.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                        {group.count > group.records.length && (
                            <p className="mt-1 px-2 text-xs text-muted-foreground">
                                and {group.count - group.records.length} more
                            </p>
                        )}
                    </div>
                ))}
            </div>
        </section>
    );
}
