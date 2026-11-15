import { Link } from '@inertiajs/react';
import {
    Columns3,
    GanttChart,
    List,
    Paperclip,
    Plus,
    Star,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { projectStatusOf } from '@/components/projects/project-meta';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/projects';
import type { SidebarProject, Tone } from '@/types';

const statusDots: Record<Tone, string> = {
    neutral: 'bg-muted-foreground/50',
    primary: 'bg-primary',
    info: 'bg-info',
    success: 'bg-success',
    warning: 'bg-warning',
    destructive: 'bg-destructive',
};

const views: { value: string; label: string; icon: LucideIcon }[] = [
    { value: 'board', label: 'Board', icon: Columns3 },
    { value: 'list', label: 'List', icon: List },
    { value: 'timeline', label: 'Timeline', icon: GanttChart },
    { value: 'files', label: 'Files', icon: Paperclip },
];

/**
 * The sidebar's Projects section: starred projects first, then recent ones,
 * each with its status. The project you're in opens out into its views.
 */
export function NavProjects({ projects }: { projects: SidebarProject[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-1 group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel className="h-7 px-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                Projects
            </SidebarGroupLabel>
            <SidebarGroupAction asChild title="New project" className="top-1.5">
                <Link href={index({ query: { new: 1 } })}>
                    <Plus />
                </Link>
            </SidebarGroupAction>
            <SidebarMenu className="gap-px">
                {projects.length === 0 && (
                    <p className="px-2 py-1 text-xs text-subtle-foreground">
                        No projects yet.
                    </p>
                )}
                {projects.map((project) => {
                    const isCurrent = isCurrentOrParentUrl(show(project.id));

                    return (
                        <SidebarMenuItem key={project.id}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrent}
                                className="h-[30px] gap-2.5 text-sidebar-foreground data-[active=true]:bg-card data-[active=true]:font-medium data-[active=true]:text-foreground data-[active=true]:shadow-card"
                            >
                                <Link href={show(project.id)} prefetch>
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'hue-dot',
                                            'ml-0.5 size-2.5 shrink-0 rounded-[3px]',
                                        )}
                                        style={hueStyle(project.hue)}
                                    />
                                    <span className="truncate">
                                        {project.name}
                                    </span>
                                    {project.is_favorite && (
                                        <Star className="size-3! shrink-0 fill-amber-400 text-amber-400" />
                                    )}
                                    <span
                                        aria-label={
                                            projectStatusOf(project.status)
                                                .label
                                        }
                                        className={cn(
                                            'ml-auto size-1.5 shrink-0 rounded-full',
                                            statusDots[
                                                projectStatusOf(project.status)
                                                    .tone
                                            ],
                                        )}
                                    />
                                </Link>
                            </SidebarMenuButton>
                            {isCurrent && (
                                <SidebarMenuSub className="mr-0 gap-px">
                                    {views.map((view) => (
                                        <SidebarMenuSubItem key={view.value}>
                                            <SidebarMenuSubButton
                                                asChild
                                                size="sm"
                                            >
                                                <Link
                                                    href={show(project.id, {
                                                        query: {
                                                            view: view.value,
                                                        },
                                                    })}
                                                >
                                                    <view.icon />
                                                    <span>{view.label}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    ))}
                                </SidebarMenuSub>
                            )}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
