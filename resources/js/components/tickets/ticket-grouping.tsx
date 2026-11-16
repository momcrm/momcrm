import type { ReactNode } from 'react';
import {
    AssigneeAvatar,
    PriorityGlyph,
    StatusGlyph,
    useTicketPriorities,
    useTicketStatuses,
} from '@/components/tickets/ticket-meta';
import type { TicketGroupBy } from '@/hooks/use-ticket-view-prefs';
import { hueStyle } from '@/lib/colors';
import type { TicketListItem } from '@/types';

export type TicketSection = {
    key: string;
    label: string;
    icon: ReactNode;
    tickets: TicketListItem[];
    /** Starts folded away, such as closed statuses among open work. */
    collapsed: boolean;
    /** A status new tickets in this section get, when grouped by status. */
    statusId: string | null;
};

const NONE = '__none__';

/**
 * The tickets on screen split into sections by status, priority, assignee or
 * project, in the team's order, for the list and table views. Empty sections
 * are dropped except for statuses, which keep a place to add tickets.
 */
export function useTicketSections(
    tickets: TicketListItem[],
    groupBy: TicketGroupBy,
    { collapseClosed = false }: { collapseClosed?: boolean } = {},
): TicketSection[] {
    const statuses = useTicketStatuses(
        ...tickets.map((ticket) => ticket.status_id),
    );
    const priorities = useTicketPriorities(
        ...tickets.map((ticket) => ticket.priority_id),
    );

    const section = (
        key: string,
        label: string,
        icon: ReactNode,
        matches: (ticket: TicketListItem) => boolean,
        extra: Partial<TicketSection> = {},
    ): TicketSection => ({
        key,
        label,
        icon,
        tickets: tickets.filter(matches),
        collapsed: false,
        statusId: null,
        ...extra,
    });

    const nonEmpty = (sections: TicketSection[]) =>
        sections.filter((item) => item.tickets.length > 0);

    switch (groupBy) {
        case 'status':
            return statuses.map((status) =>
                section(
                    status.id,
                    status.name,
                    <StatusGlyph status={status} />,
                    (ticket) => ticket.status_id === status.id,
                    {
                        collapsed:
                            collapseClosed && status.category === 'closed',
                        statusId: status.is_archived ? null : status.id,
                    },
                ),
            );
        case 'priority':
            return nonEmpty([
                ...priorities.map((priority) =>
                    section(
                        priority.id,
                        priority.name,
                        <PriorityGlyph priority={priority} />,
                        (ticket) => ticket.priority_id === priority.id,
                    ),
                ),
                section(
                    NONE,
                    'No priority',
                    <PriorityGlyph priority={undefined} />,
                    (ticket) =>
                        !priorities.some(
                            (priority) => priority.id === ticket.priority_id,
                        ),
                ),
            ]);
        case 'assignee':
            return nonEmpty([
                ...uniqueBy(
                    tickets.filter((ticket) => ticket.assignee_id),
                    (ticket) => ticket.assignee_id as string,
                )
                    .sort((a, b) =>
                        (a.assignee_name ?? '').localeCompare(
                            b.assignee_name ?? '',
                        ),
                    )
                    .map((first) =>
                        section(
                            first.assignee_id as string,
                            first.assignee_name ?? 'Someone',
                            <AssigneeAvatar name={first.assignee_name} />,
                            (ticket) =>
                                ticket.assignee_id === first.assignee_id,
                        ),
                    ),
                section(
                    NONE,
                    'Unassigned',
                    <AssigneeAvatar name={null} />,
                    (ticket) => !ticket.assignee_id,
                ),
            ]);
        case 'project':
            return nonEmpty([
                ...uniqueBy(
                    tickets.filter((ticket) => ticket.project_id),
                    (ticket) => ticket.project_id as string,
                )
                    .sort((a, b) =>
                        (a.project_name ?? '').localeCompare(
                            b.project_name ?? '',
                        ),
                    )
                    .map((first) =>
                        section(
                            first.project_id as string,
                            first.project_name ?? 'Project',
                            <ProjectDot hue={first.project_hue} />,
                            (ticket) => ticket.project_id === first.project_id,
                        ),
                    ),
                section(
                    NONE,
                    'No project',
                    <ProjectDot hue={null} />,
                    (ticket) => !ticket.project_id,
                ),
            ]);
        default:
            return nonEmpty([section('all', 'All tickets', null, () => true)]);
    }
}

function ProjectDot({ hue }: { hue: number | null }) {
    return (
        <span
            aria-hidden
            className="size-2 shrink-0 rounded-[3px] hue-dot"
            style={hueStyle(hue)}
        />
    );
}

function uniqueBy<T>(items: T[], key: (item: T) => string): T[] {
    const seen = new Set<string>();

    return items.filter((item) => {
        const value = key(item);

        if (seen.has(value)) {
            return false;
        }

        seen.add(value);

        return true;
    });
}
