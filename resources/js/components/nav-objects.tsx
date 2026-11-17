import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ObjectIcon } from '@/components/custom-objects/object-icon';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
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
 * for those who can add one.
 */
export function NavObjects() {
    const { sidebarCounts, canManageCustomObjects } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const objects = sidebarCounts?.objects ?? [];

    if (objects.length === 0 && !canManageCustomObjects) {
        return null;
    }

    return (
        <SidebarGroup className="px-2 py-1 group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel className="h-7 px-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                Objects
            </SidebarGroupLabel>
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
                {objects.length === 0 && (
                    <p className="px-2 py-1 text-xs text-subtle-foreground">
                        No objects yet.
                    </p>
                )}
                {objects.map((object) => (
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
