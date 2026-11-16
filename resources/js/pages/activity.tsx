import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import NativeSelect from '@/components/native-select';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import { describe } from '@/components/tickets/ticket-conversation';
import { AssigneeAvatar } from '@/components/tickets/ticket-meta';
import { timeAgo } from '@/lib/format';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import { activity as activityRoute } from '@/routes';
import type { ActivityItem, CustomerOption, Paginated } from '@/types';

type FeedItem = ActivityItem & { url: string | null };

const filters = [
    { value: 'all', label: 'All' },
    { value: 'status', label: 'Status changes' },
    { value: 'comments', label: 'Comments' },
    { value: 'projects', label: 'Projects' },
    { value: 'files', label: 'Files' },
];

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

function subjectOf(item: FeedItem): string | null {
    const details = item.details ?? {};

    if (
        typeof details.number === 'number' &&
        typeof details.title === 'string'
    ) {
        return `#${details.number} ${details.title}`;
    }

    if (typeof details.name === 'string') {
        return details.name;
    }

    return typeof details.number === 'number' ? `#${details.number}` : null;
}

/**
 * Everything the team did on tickets and projects, one sentence per change,
 * grouped by day.
 */
export default function ActivityPage({
    activities,
    filters: current,
    members,
}: {
    activities: Paginated<FeedItem>;
    filters: { filter: string; person: string | null };
    members: CustomerOption[];
}) {
    const only = ['activities', 'filters'];
    const days = activities.data.reduce<Record<string, FeedItem[]>>(
        (groups, item) => {
            const day = item.created_at
                ? dayFormatter.format(new Date(item.created_at))
                : '';
            (groups[day] ??= []).push(item);

            return groups;
        },
        {},
    );

    return (
        <>
            <Head title="Activity" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Activity"
                    description="What the team has been doing on tickets and projects."
                />

                <div className="flex flex-wrap items-center gap-2">
                    <nav
                        aria-label="Filter"
                        className="flex gap-0.5 rounded-md bg-muted p-0.5"
                    >
                        {filters.map((filter) => (
                            <button
                                key={filter.value}
                                type="button"
                                onClick={() =>
                                    visitWithQuery(
                                        {
                                            filter:
                                                filter.value === 'all'
                                                    ? null
                                                    : filter.value,
                                        },
                                        { only },
                                    )
                                }
                                className={cn(
                                    'rounded-[5px] px-2.5 py-0.5 text-sm',
                                    current.filter === filter.value
                                        ? 'bg-card shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {filter.label}
                            </button>
                        ))}
                    </nav>
                    <NativeSelect
                        aria-label="Person"
                        value={current.person ?? ''}
                        onChange={(event) =>
                            visitWithQuery(
                                { person: event.target.value },
                                { only },
                            )
                        }
                        className="ml-auto h-control-sm w-48"
                    >
                        <option value="">Everyone</option>
                        {members.map((member) => (
                            <option key={member.id} value={member.id}>
                                {member.name}
                            </option>
                        ))}
                    </NativeSelect>
                </div>

                {activities.data.length === 0 ? (
                    <EmptyState
                        title="Nothing yet"
                        description="Changes to tickets and projects show up here as they happen."
                    />
                ) : (
                    Object.entries(days).map(([day, items]) => (
                        <section key={day} className="space-y-2">
                            <h2 className="text-xs font-medium tracking-wide text-subtle-foreground uppercase">
                                {day}
                            </h2>
                            <ol className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                                {items.map((item) => {
                                    const subject = subjectOf(item);

                                    return (
                                        <li
                                            key={item.id}
                                            className="flex items-center gap-3 px-3 py-2.5 text-sm"
                                        >
                                            <AssigneeAvatar
                                                name={item.actor}
                                                className="size-6 text-[10px]"
                                            />
                                            <p className="min-w-0 flex-1 truncate">
                                                <span className="font-medium">
                                                    {item.actor ?? 'Someone'}
                                                </span>{' '}
                                                <span className="text-muted-foreground">
                                                    {describe(item)}
                                                </span>
                                                {subject && (
                                                    <>
                                                        {' '}
                                                        {item.url ? (
                                                            <Link
                                                                href={item.url}
                                                                className="font-medium hover:underline"
                                                            >
                                                                {subject}
                                                            </Link>
                                                        ) : (
                                                            <span className="font-medium">
                                                                {subject}
                                                            </span>
                                                        )}
                                                    </>
                                                )}
                                            </p>
                                            <span className="shrink-0 text-xs text-subtle-foreground">
                                                {timeAgo(item.created_at)}
                                            </span>
                                        </li>
                                    );
                                })}
                            </ol>
                        </section>
                    ))
                )}

                <Pager page={activities} only="activities" />
            </div>
        </>
    );
}

ActivityPage.layout = {
    breadcrumbs: [{ title: 'Activity', href: activityRoute() }],
};
