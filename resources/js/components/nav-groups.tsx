import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import { members } from '@/routes';
import { show } from '@/routes/groups';

/**
 * The sidebar's Groups section, linking to each group's page.
 */
export function NavGroups() {
    const { sidebarCounts } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const groups = sidebarCounts?.groups ?? [];

    return (
        <SidebarGroup className="px-2 py-1 group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel className="h-7 px-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                Groups
            </SidebarGroupLabel>
            <SidebarGroupAction asChild title="All groups" className="top-1.5">
                <Link href={members({ query: { tab: 'groups' } })}>
                    <Plus />
                </Link>
            </SidebarGroupAction>
            <SidebarMenu className="gap-px">
                {groups.length === 0 && (
                    <p className="px-2 py-1 text-xs text-subtle-foreground">
                        No groups yet.
                    </p>
                )}
                {groups.map((group) => (
                    <SidebarMenuItem key={group.id}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentOrParentUrl(show(group.id))}
                            className="h-[30px] gap-2.5 text-sidebar-foreground data-[active=true]:bg-card data-[active=true]:font-medium data-[active=true]:text-foreground data-[active=true]:shadow-card"
                        >
                            <Link href={show(group.id)} prefetch>
                                <span
                                    aria-hidden
                                    className={cn(
                                        'ml-0.5 size-2.5 shrink-0 rounded-full',
                                        colorDot[group.color],
                                    )}
                                />
                                <span className="truncate">{group.name}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
