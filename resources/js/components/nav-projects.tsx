import { Link } from '@inertiajs/react';
import { Plus, Star } from 'lucide-react';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { colorDot } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/projects';
import type { SidebarProject } from '@/types';

/**
 * The sidebar's Projects section: starred projects first, then recent ones.
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
                {projects.map((project) => (
                    <SidebarMenuItem key={project.id}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentOrParentUrl(show(project.id))}
                            className="h-[30px] gap-2.5 text-sidebar-foreground data-[active=true]:bg-card data-[active=true]:font-medium data-[active=true]:text-foreground data-[active=true]:shadow-card"
                        >
                            <Link href={show(project.id)} prefetch>
                                <span
                                    aria-hidden
                                    className={cn(
                                        'ml-0.5 size-2.5 shrink-0 rounded-[3px]',
                                        colorDot[project.color],
                                    )}
                                />
                                <span className="truncate">{project.name}</span>
                                {project.is_favorite && (
                                    <Star className="ml-auto size-3! fill-amber-400 text-amber-400" />
                                )}
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
