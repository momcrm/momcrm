import { router } from '@inertiajs/react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import {
    MemberAvatars,
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
    RestrictedLock,
    StarMark,
} from '@/components/projects/project-meta';
import { toggleProjectStar } from '@/components/projects/project-card';
import { formatDay } from '@/components/tickets/ticket-meta';
import { show } from '@/routes/projects';
import type { ProjectListItem } from '@/types';

const column = dataTableColumns<ProjectListItem>();

const columns = column.columns([
    column.accessor('name', {
        header: 'Project',
        enableSorting: true,
        cell: (info) => (
            <span className="flex min-w-0 items-center gap-2.5">
                <ProjectIcon
                    icon={info.row.original.icon}
                    color={info.row.original.color}
                    size="sm"
                />
                <span className="truncate font-medium">{info.getValue()}</span>
                {info.row.original.visibility === 'restricted' && (
                    <RestrictedLock />
                )}
                <button
                    type="button"
                    aria-label={
                        info.row.original.is_favorite
                            ? 'Unstar project'
                            : 'Star project'
                    }
                    onClick={(event) => {
                        event.stopPropagation();
                        toggleProjectStar(info.row.original.id);
                    }}
                    className="rounded p-0.5 hover:bg-accent"
                >
                    <StarMark isFavorite={info.row.original.is_favorite} />
                </button>
            </span>
        ),
    }),
    column.accessor('status', {
        header: 'Status',
        enableSorting: true,
        meta: { className: 'w-32' },
        cell: (info) => <ProjectStatusPill status={info.getValue()} />,
    }),
    column.accessor('customer_name', {
        header: 'Customer',
        meta: { className: 'hidden md:table-cell w-44' },
        cell: (info) => (
            <span className="truncate text-muted-foreground">
                {info.getValue() ?? 'Internal'}
            </span>
        ),
    }),
    column.display({
        id: 'progress',
        header: 'Progress',
        meta: { className: 'hidden lg:table-cell w-44' },
        cell: (info) => (
            <ProgressBar
                closed={info.row.original.closed_tickets_count}
                total={info.row.original.tickets_count}
            />
        ),
    }),
    column.accessor('lead_name', {
        header: 'Lead',
        meta: { className: 'hidden xl:table-cell w-36' },
        cell: (info) => (
            <span className="truncate text-muted-foreground">
                {info.getValue() ?? '—'}
            </span>
        ),
    }),
    column.display({
        id: 'members',
        header: 'Members',
        meta: { className: 'hidden md:table-cell w-28' },
        cell: (info) => (
            <MemberAvatars members={info.row.original.members} max={3} />
        ),
    }),
    column.accessor('due_on', {
        header: 'Due',
        enableSorting: true,
        meta: { className: 'w-28' },
        cell: (info) => (
            <span className="text-muted-foreground">
                {info.getValue() ? formatDay(info.getValue()!) : '—'}
            </span>
        ),
    }),
]);

export default function ProjectTable({
    projects,
    sort,
    onSortChange,
}: {
    projects: ProjectListItem[];
    sort: DataTableSort;
    onSortChange: (sort: DataTableSort) => void;
}) {
    return (
        <DataTable
            columns={columns}
            data={projects}
            getRowId={(project) => project.id}
            sort={sort}
            onSortChange={onSortChange}
            onRowClick={(project) => router.visit(show.url(project.id))}
        />
    );
}
