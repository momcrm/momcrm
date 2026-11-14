import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavSection as NavSectionType } from '@/lib/workspace-navigation';
import { cn } from '@/lib/utils';

/**
 * One labelled group of sidebar links. The current page's link is lifted onto
 * a card, and counts sit on the right.
 */
export function NavSection({
    section,
    className,
}: {
    section: NavSectionType;
    className?: string;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className={cn('px-2 py-1', className)}>
            {section.title && (
                <SidebarGroupLabel className="h-7 px-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                    {section.title}
                </SidebarGroupLabel>
            )}
            <SidebarMenu className="gap-px">
                {section.items.map((item) => {
                    const isActive = isCurrentOrParentUrl(item.href);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
                                tooltip={{ children: item.title }}
                                className="h-[30px] gap-2.5 text-sidebar-foreground data-[active=true]:bg-card data-[active=true]:font-medium data-[active=true]:text-foreground data-[active=true]:shadow-card"
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                            {item.badge ? (
                                <SidebarMenuBadge className="top-[5px] text-[11px] text-subtle-foreground">
                                    {item.badge}
                                </SidebarMenuBadge>
                            ) : null}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
