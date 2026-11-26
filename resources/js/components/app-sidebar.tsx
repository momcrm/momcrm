import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { GettingStartedNav } from '@/components/getting-started-nav';
import { NavGroups } from '@/components/nav-groups';
import { NavObjects } from '@/components/nav-objects';
import { NavProjects } from '@/components/nav-projects';
import { NavSection } from '@/components/nav-section';
import { NavSettings } from '@/components/nav-settings';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { isSettingsUrl } from '@/hooks/use-settings-navigation';
import {
    settingsDestination,
    workspaceNavigation,
} from '@/lib/workspace-navigation';
import { dashboard } from '@/routes';

/**
 * The sidebar beside every page. On a Settings page it shows the Settings
 * menu instead of the workspace's, with a way back to the page you left.
 * Each menu is keyed so it starts at the top rather than where the other one
 * was scrolled to. On a phone the sidebar is a sheet over the page, so it
 * closes once a link in it has been followed. Your account menu is the
 * avatar in the bar above the page, so the sidebar doesn't repeat it.
 */
export function AppSidebar() {
    const { props, url } = usePage();
    const { sidebarCounts } = props;
    const { currentUrl } = useCurrentUrl();
    const isInSettings = isSettingsUrl(currentUrl);
    const [lastWorkspaceUrl, setLastWorkspaceUrl] = useState(dashboard().url);

    const { setOpenMobile } = useSidebar();

    if (!isInSettings && lastWorkspaceUrl !== url) {
        setLastWorkspaceUrl(url);
    }

    useEffect(
        () => router.on('navigate', () => setOpenMobile(false)),
        [setOpenMobile],
    );

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="flex-row items-center gap-1 p-2">
                <div className="min-w-0 flex-1">
                    <TeamSwitcher />
                </div>
                <SidebarTrigger className="size-7 text-muted-foreground group-data-[collapsible=icon]:hidden" />
            </SidebarHeader>

            <SidebarContent
                key={isInSettings ? 'settings' : 'workspace'}
                className="gap-1"
            >
                {isInSettings ? (
                    <NavSettings backHref={lastWorkspaceUrl} />
                ) : (
                    <>
                        <GettingStartedNav />
                        {workspaceNavigation(sidebarCounts).map(
                            (section, index) => (
                                <NavSection
                                    key={section.title ?? index}
                                    section={section}
                                    foldable
                                />
                            ),
                        )}
                        {sidebarCounts && (
                            <NavProjects projects={sidebarCounts.projects} />
                        )}
                        {sidebarCounts && <NavGroups />}
                        {sidebarCounts && <NavObjects />}
                    </>
                )}
            </SidebarContent>

            {!isInSettings && (
                <SidebarFooter className="border-t border-sidebar-border p-2">
                    <NavSection
                        section={{ items: [settingsDestination()] }}
                        className="p-0"
                    />
                </SidebarFooter>
            )}
        </Sidebar>
    );
}
