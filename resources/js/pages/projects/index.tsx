import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List, Plus, Table2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import NativeSelect from '@/components/native-select';
import PageHeader from '@/components/page-header';
import ProjectCard from '@/components/projects/project-card';
import ProjectFormDialog from '@/components/projects/project-form-dialog';
import {
    MemberAvatars,
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
    projectStatuses,
} from '@/components/projects/project-meta';
import ProjectTable from '@/components/projects/project-table';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { LabelChips, useLabels } from '@/components/labels';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { visitWithQuery } from '@/lib/query';
import { index, show } from '@/routes/projects';
import type { ProjectFormOptions, ProjectListItem } from '@/types';

type Filters = {
    search: string | null;
    status: string | null;
    label: string | null;
    sort: string;
    direction: 'asc' | 'desc';
    view: 'grid' | 'list' | 'table';
};

const views: { value: Filters['view']; label: string; icon: LucideIcon }[] = [
    { value: 'grid', label: 'Grid', icon: LayoutGrid },
    { value: 'list', label: 'List', icon: List },
    { value: 'table', label: 'Table', icon: Table2 },
];

const sorts = [
    { value: 'name', label: 'Name' },
    { value: 'due_on', label: 'Due date' },
    { value: 'updated_at', label: 'Recently updated' },
    { value: 'status', label: 'Status' },
];

const only = ['projects', 'filters'];

export default function ProjectsIndex({
    projects,
    filters,
    projectOptions,
}: {
    projects: ProjectListItem[];
    filters: Filters;
    projectOptions: ProjectFormOptions;
}) {
    const [creating, setCreating] = useState(
        () =>
            typeof window !== 'undefined' &&
            new URLSearchParams(window.location.search).has('new'),
    );
    const isFiltered = Boolean(
        filters.search || filters.status || filters.label,
    );
    const labels = useLabels(filters.label);

    return (
        <>
            <Head title="Projects" />

            <div className="flex flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Projects"
                    description="Customer jobs and your own work, with the tickets to get them done."
                    actions={
                        <Button onClick={() => setCreating(true)}>
                            <Plus />
                            New project
                        </Button>
                    }
                />

                <div className="flex flex-wrap items-center gap-2">
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        size="sm"
                        value={filters.view}
                        onValueChange={(view) =>
                            view &&
                            visitWithQuery(
                                { view: view === 'grid' ? null : view },
                                { only },
                            )
                        }
                        aria-label="View"
                        className="bg-card"
                    >
                        {views.map((view) => (
                            <ToggleGroupItem
                                key={view.value}
                                value={view.value}
                                aria-label={view.label}
                                className="gap-1.5 px-2.5"
                            >
                                <view.icon className="size-3.5" />
                                {view.label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                    <NativeSelect
                        aria-label="Status"
                        value={filters.status ?? ''}
                        onChange={(event) =>
                            visitWithQuery(
                                { status: event.target.value },
                                { only },
                            )
                        }
                        className="h-control-sm w-40"
                    >
                        <option value="">Any status</option>
                        {projectStatuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </NativeSelect>
                    {labels.length > 0 && (
                        <NativeSelect
                            aria-label="Label"
                            value={filters.label ?? ''}
                            onChange={(event) =>
                                visitWithQuery(
                                    { label: event.target.value },
                                    { only },
                                )
                            }
                            className="h-control-sm w-40"
                        >
                            <option value="">Any label</option>
                            {labels.map((label) => (
                                <option key={label.id} value={label.id}>
                                    {label.name}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                    {filters.view !== 'table' && (
                        <NativeSelect
                            aria-label="Sort by"
                            value={filters.sort}
                            onChange={(event) =>
                                visitWithQuery(
                                    {
                                        sort: event.target.value,
                                        direction:
                                            event.target.value === 'updated_at'
                                                ? 'desc'
                                                : 'asc',
                                    },
                                    { only },
                                )
                            }
                            className="h-control-sm w-48"
                        >
                            {sorts.map((sort) => (
                                <option key={sort.value} value={sort.value}>
                                    Sort: {sort.label}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                    <div className="ml-auto w-full sm:w-64">
                        <SearchInput
                            value={filters.search}
                            placeholder="Search projects"
                            only={only}
                        />
                    </div>
                </div>

                {projects.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title="No projects match"
                            description="Try a shorter search or another status."
                        />
                    ) : (
                        <EmptyState
                            title="No projects yet"
                            description="A project groups the tickets for a customer job or your own work, with milestones and a due date."
                            action={
                                <Button onClick={() => setCreating(true)}>
                                    New project
                                </Button>
                            }
                        />
                    )
                ) : filters.view === 'table' ? (
                    <ProjectTable
                        projects={projects}
                        sort={{
                            column: filters.sort,
                            direction: filters.direction,
                        }}
                        onSortChange={({ column, direction }) =>
                            visitWithQuery(
                                { sort: column, direction },
                                { only },
                            )
                        }
                    />
                ) : filters.view === 'list' ? (
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card shadow-xs">
                        {projects.map((project) => (
                            <li key={project.id}>
                                <Link
                                    href={show(project.id)}
                                    className="flex h-12 items-center gap-3 px-3 text-sm hover:bg-accent/50"
                                >
                                    <ProjectIcon
                                        icon={project.icon}
                                        hue={project.hue}
                                        size="sm"
                                    />
                                    <span className="flex min-w-0 flex-1 items-center gap-2">
                                        <span className="truncate font-medium">
                                            {project.name}
                                        </span>
                                        <LabelChips
                                            labels={project.labels}
                                            className="hidden flex-nowrap sm:flex"
                                        />
                                    </span>
                                    <span className="hidden w-40 truncate text-xs text-muted-foreground md:block">
                                        {project.customer_name ?? 'Internal'}
                                    </span>
                                    <span className="hidden w-40 lg:block">
                                        <ProgressBar
                                            closed={
                                                project.closed_tickets_count
                                            }
                                            total={project.tickets_count}
                                        />
                                    </span>
                                    <ProjectStatusPill
                                        status={project.status}
                                    />
                                    <MemberAvatars
                                        members={project.members}
                                        max={3}
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                )}
            </div>

            <ProjectFormDialog
                open={creating}
                onOpenChange={setCreating}
                options={projectOptions}
            />
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
