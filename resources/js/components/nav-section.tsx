import { Link } from '@inertiajs/react';
import {
    NavSectionLabel,
    useNavSectionFold,
} from '@/components/nav-section-label';
import {
    SidebarGroup,
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
 * a card, and counts sit on the right. A foldable group folds away from its
 * heading, down to the link for the page you're on.
 */
export function NavSection({
    section,
    foldable = false,
    className,
}: {
    section: NavSectionType;
    foldable?: boolean;
    className?: string;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const fold = useNavSectionFold(section.title ?? '');
    const canFold = foldable && section.title !== undefined;
    const isFolded = canFold && fold.isFolded;

    return (
        <SidebarGroup className={cn('px-2 py-1', className)}>
            {section.title && (
                <NavSectionLabel
                    title={section.title}
                    fold={canFold ? fold : undefined}
                />
            )}
            <SidebarMenu className="gap-px">
                {section.items.map((item) => {
                    const isActive = isCurrentOrParentUrl(item.href);

                    if (isFolded && !isActive) {
                        return null;
                    }

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
                                <SidebarMenuBadge
                                    className={cn(
                                        'top-[5px] text-[11px]',
                                        item.highlightBadge
                                            ? 'h-[18px] min-w-[18px] rounded-full bg-primary px-1 text-primary-foreground peer-hover/menu-button:text-primary-foreground peer-data-[active=true]/menu-button:text-primary-foreground'
                                            : 'text-subtle-foreground',
                                    )}
                                >
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
