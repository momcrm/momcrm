import { Head, Link } from '@inertiajs/react';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import {
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
} from '@/components/projects/project-meta';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { projects as projectsTab } from '@/routes/customers';
import { show as showProject } from '@/routes/projects';
import type { CustomerWorkspace, ProjectListItem } from '@/types';

export default function CustomerProjects({
    workspace,
    projects,
}: {
    workspace: CustomerWorkspace;
    projects: ProjectListItem[];
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Projects',
        href: projectsTab(customer.id),
    });

    return (
        <>
            <Head title={`${customer.name} · Projects`} />
            <TabHeader
                title="Projects"
                description="Bigger pieces of work for this customer, with their progress."
            />
            {projects.length > 0 ? (
                <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                    {projects.map((project) => (
                        <li key={project.id}>
                            <Link
                                href={showProject(project.id)}
                                className="flex h-11 items-center gap-3 px-3 text-sm hover:bg-accent/50"
                            >
                                <ProjectIcon
                                    icon={project.icon}
                                    hue={project.hue}
                                    size="sm"
                                />
                                <span className="min-w-0 flex-1 truncate font-medium">
                                    {project.name}
                                </span>
                                <span className="hidden w-40 md:block">
                                    <ProgressBar
                                        closed={project.closed_tickets_count}
                                        total={project.tickets_count}
                                    />
                                </span>
                                <ProjectStatusPill status={project.status} />
                            </Link>
                        </li>
                    ))}
                </ul>
            ) : (
                <EmptyState
                    title="No projects yet"
                    description={`Projects you run for ${customer.name} show up here, with how far along each one is.`}
                />
            )}
        </>
    );
}
