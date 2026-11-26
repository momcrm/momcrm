import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ObjectIcon } from '@/components/custom-objects/object-icon';
import {
    NavSectionLabel,
    useNavSectionFold,
} from '@/components/nav-section-label';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { edit } from '@/routes/custom-objects';
import { index } from '@/routes/records';

/**
 * The sidebar's Objects section: one entry per custom object the team has
 * defined, linking to its records. Hidden until there is an object, except
 * for those who can add one. Folded away, it keeps only the object you're in.
 */
export function NavObjects() {
    const { sidebarCounts, canManageCustomObjects } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const fold = useNavSectionFold('Objects');
    const objects = sidebarCounts?.objects ?? [];

    if (objects.length === 0 && !canManageCustomObjects) {
        return null;
    }

    const visibleObjects = objects.filter(
        (object) => !fold.isFolded || isCurrentOrParentUrl(index(object.id)),
    );

    return (
        <SidebarGroup className="px-2 py-1 group-data-[collapsible=icon]:hidden">
            <NavSectionLabel title="Objects" fold={fold} />
            {canManageCustomObjects && (
                <SidebarGroupAction
                    asChild
                    title="Manage objects"
                    className="top-1.5"
                >
                    <Link href={edit()}>
                        <Plus />
                    </Link>
                </SidebarGroupAction>
            )}
            <SidebarMenu className="gap-px">
                {objects.length === 0 && !fold.isFolded && (
                    <p className="px-2 py-1 text-xs text-subtle-foreground">
                        No objects yet.
                    </p>
                )}
                {visibleObjects.map((object) => (
                    <SidebarMenuItem key={object.id}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentOrParentUrl(index(object.id))}
                            className="h-[30px] gap-2.5 text-sidebar-foreground data-[active=true]:bg-card data-[active=true]:font-medium data-[active=true]:text-foreground data-[active=true]:shadow-card"
                        >
                            <Link href={index(object.id)} prefetch>
                                <ObjectIcon
                                    icon={object.icon}
                                    hue={object.hue}
                                    size="sm"
                                />
                                <span className="truncate">
                                    {object.name_plural}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
