import type { ActivityItem } from '@/types';

/**
 * What a change was made to, from its details: "#12 Printer offline" for a
 * ticket, the name for a project, or null when there's nothing to show.
 */
export function activitySubject(item: ActivityItem): string | null {
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
