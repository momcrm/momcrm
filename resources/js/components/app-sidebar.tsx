import { usePage } from '@inertiajs/react';
import { GettingStartedNav } from '@/components/getting-started-nav';
import { NavProjects } from '@/components/nav-projects';
import { NavSection } from '@/components/nav-section';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import {
    footerNavigation,
    workspaceNavigation,
} from '@/lib/workspace-navigation';

export function AppSidebar() {
    const { sidebarCounts } = usePage().props;

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="flex-row items-center gap-1 p-2">
                <div className="min-w-0 flex-1">
                    <TeamSwitcher />
                </div>
                <SidebarTrigger className="size-7 text-muted-foreground group-data-[collapsible=icon]:hidden" />
            </SidebarHeader>

            <SidebarContent className="gap-1">
                <GettingStartedNav />
                {workspaceNavigation(sidebarCounts).map((section, index) => (
                    <NavSection
                        key={section.title ?? index}
                        section={section}
                    />
                ))}
                {sidebarCounts && (
                    <NavProjects projects={sidebarCounts.projects} />
                )}
            </SidebarContent>

            <SidebarFooter className="gap-1 p-2">
                <NavSection
                    section={{ items: footerNavigation() }}
                    className="p-0"
                />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
