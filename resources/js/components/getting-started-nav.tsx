import { Link, usePage } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

/**
 * A "Getting started" entry pinned in the sidebar with a progress ring, so the
 * checklist is one click away until it's finished or dismissed.
 */
export function GettingStartedNav() {
    const { gettingStarted } = usePage().props;

    if (!gettingStarted || gettingStarted.completed === gettingStarted.total) {
        return null;
    }

    const radius = 7;
    const circumference = 2 * Math.PI * radius;
    const progress = gettingStarted.completed / gettingStarted.total;

    return (
        <SidebarGroup className="px-2 pb-0">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        tooltip={{
                            children: `Getting started: ${gettingStarted.completed} of ${gettingStarted.total}`,
                        }}
                    >
                        <Link href={dashboard()}>
                            <svg
                                viewBox="0 0 18 18"
                                className="size-4 shrink-0 -rotate-90"
                                aria-hidden
                            >
                                <circle
                                    cx="9"
                                    cy="9"
                                    r={radius}
                                    fill="none"
                                    stroke="currentColor"
                                    strokeOpacity="0.2"
                                    strokeWidth="2.5"
                                />
                                <circle
                                    cx="9"
                                    cy="9"
                                    r={radius}
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    strokeDasharray={circumference}
                                    strokeDashoffset={
                                        circumference * (1 - progress)
                                    }
                                    strokeLinecap="round"
                                />
                            </svg>
                            <span>Getting started</span>
                            <span className="ml-auto text-xs text-muted-foreground">
                                {gettingStarted.completed}/
                                {gettingStarted.total}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    );
}
