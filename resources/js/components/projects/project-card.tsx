import { Link, router } from '@inertiajs/react';
import { LabelChips } from '@/components/labels';
import { CalendarDays } from 'lucide-react';
import { formatDay } from '@/components/tickets/ticket-meta';
import {
    MemberAvatars,
    ProgressBar,
    ProjectIcon,
    ProjectStatusPill,
    RestrictedLock,
    StarMark,
} from '@/components/projects/project-meta';
import { toggle as toggleFavorite } from '@/routes/favorites';
import { show } from '@/routes/projects';
import type { ProjectListItem } from '@/types';

export function toggleProjectStar(projectId: string) {
    router.post(
        toggleFavorite.url(),
        { favoritable_type: 'project', favoritable_id: projectId },
        { preserveScroll: true, preserveState: true },
    );
}

/**
 * A project in the grid: icon, name and star, status, description, progress,
 * then due date and members.
 */
export default function ProjectCard({ project }: { project: ProjectListItem }) {
    return (
        <article className="group relative flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-xs transition hover:border-border-strong">
            <div className="flex items-start gap-3">
                <ProjectIcon icon={project.icon} hue={project.hue} />
                <div className="min-w-0 flex-1">
                    <h3 className="flex items-center gap-1.5 font-medium">
                        <Link
                            href={show(project.id)}
                            className="truncate after:absolute after:inset-0"
                        >
                            {project.name}
                        </Link>
                        {project.visibility === 'restricted' && (
                            <RestrictedLock />
                        )}
                    </h3>
                    <p className="truncate text-xs text-muted-foreground">
                        {project.customer_name ?? 'Internal'}
                    </p>
                </div>
                <button
                    type="button"
                    aria-label={
                        project.is_favorite ? 'Unstar project' : 'Star project'
                    }
                    onClick={() => toggleProjectStar(project.id)}
                    className="relative z-10 rounded p-1 hover:bg-accent"
                >
                    <StarMark isFavorite={project.is_favorite} />
                </button>
            </div>

            <div className="flex flex-wrap items-center gap-1.5">
                <ProjectStatusPill status={project.status} />
                <LabelChips labels={project.labels} />
            </div>

            <p className="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                {project.description ?? 'No description yet.'}
            </p>

            <div className="space-y-1.5">
                <div className="flex justify-between text-xs text-muted-foreground">
                    <span>
                        {project.closed_tickets_count} of{' '}
                        {project.tickets_count} tickets done
                    </span>
                    {project.overdue_tickets_count > 0 && (
                        <span className="font-medium text-destructive">
                            {project.overdue_tickets_count} overdue
                        </span>
                    )}
                </div>
                <ProgressBar
                    closed={project.closed_tickets_count}
                    total={project.tickets_count}
                />
            </div>

            <div className="flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                <span className="flex items-center gap-1">
                    <CalendarDays className="size-3" />
                    {project.due_on ? formatDay(project.due_on) : 'No due date'}
                </span>
                <MemberAvatars members={project.members} />
            </div>
        </article>
    );
}
