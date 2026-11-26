import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { NavSection } from '@/components/nav-section';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useSettingsNavigation } from '@/hooks/use-settings-navigation';

/**
 * What the sidebar shows on a Settings page: a way back to where you were,
 * then every settings page you may open.
 */
export function NavSettings({ backHref }: { backHref: string }) {
    const sections = useSettingsNavigation();

    return (
        <>
            <SidebarGroup className="px-2 pt-1 pb-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            tooltip={{ children: 'Back to app' }}
                            className="h-[30px] gap-2.5 font-medium text-foreground"
                        >
                            <Link href={backHref}>
                                <ArrowLeft />
                                <span>Back to app</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
            <nav aria-label="Settings" className="flex flex-col gap-1">
                {sections.map((section) => (
                    <NavSection key={section.title} section={section} />
                ))}
            </nav>
        </>
    );
}
