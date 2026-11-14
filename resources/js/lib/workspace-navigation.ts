import {
    Activity,
    Archive,
    CalendarDays,
    GanttChart,
    Inbox,
    LayoutDashboard,
    UsersRound,
    Building2,
    CircleUser,
    FolderKanban,
    Home,
    Star,
    Palette,
    Receipt,
    Settings,
    Ticket,
    Users,
} from 'lucide-react';
import {
    activity,
    archive,
    calendar,
    dashboard,
    designSystem,
    inbox,
    members,
    overview,
    timeline,
} from '@/routes';
import { index as contactsIndex } from '@/routes/contacts';
import { index as customersIndex } from '@/routes/customers';
import { index as favoritesIndex } from '@/routes/favorites';
import { index as invoicesIndex } from '@/routes/invoices';
import { edit as profileEdit } from '@/routes/profile';
import { index as projectsIndex } from '@/routes/projects';
import { mine as myTickets, index as ticketsIndex } from '@/routes/tickets';
import type { NavItem } from '@/types';

export type NavSection = {
    /** Shown above the items; the first section has none. */
    title?: string;
    items: NavItem[];
};

export type SidebarCounts = {
    openTickets: number;
    myOpenTickets: number;
    unreadNotifications: number;
};

/**
 * Every place the sidebar, the ⌘K palette and the "G then key" shortcuts can
 * take you, grouped the way the sidebar shows them.
 */
export function workspaceNavigation(
    counts?: SidebarCounts | null,
): NavSection[] {
    return [
        {
            items: [
                {
                    title: 'Home',
                    href: dashboard(),
                    icon: Home,
                    shortcut: 'h',
                },
                {
                    title: 'Inbox',
                    href: inbox(),
                    icon: Inbox,
                    badge: counts?.unreadNotifications,
                    highlightBadge: true,
                    shortcut: 'i',
                },
                {
                    title: 'My tickets',
                    href: myTickets(),
                    icon: CircleUser,
                    badge: counts?.myOpenTickets,
                    shortcut: 'm',
                },
                {
                    title: 'Favorites',
                    href: favoritesIndex(),
                    icon: Star,
                    shortcut: 'f',
                },
            ],
        },
        {
            title: 'Workspace',
            items: [
                {
                    title: 'Overview',
                    href: overview(),
                    icon: LayoutDashboard,
                    shortcut: 'w',
                },
                {
                    title: 'Projects',
                    href: projectsIndex(),
                    icon: FolderKanban,
                    shortcut: 'p',
                },
                {
                    title: 'Tickets',
                    href: ticketsIndex(),
                    icon: Ticket,
                    badge: counts?.openTickets,
                    shortcut: 't',
                },
                {
                    title: 'Calendar',
                    href: calendar(),
                    icon: CalendarDays,
                    shortcut: 'l',
                },
                {
                    title: 'Timeline',
                    href: timeline(),
                    icon: GanttChart,
                    shortcut: 'r',
                },
                {
                    title: 'Members',
                    href: members(),
                    icon: UsersRound,
                    shortcut: 'u',
                },
                {
                    title: 'Activity',
                    href: activity(),
                    icon: Activity,
                    shortcut: 'y',
                },
                {
                    title: 'Archive',
                    href: archive(),
                    icon: Archive,
                    shortcut: 'a',
                },
            ],
        },
        {
            title: 'CRM',
            items: [
                {
                    title: 'Customers',
                    href: customersIndex(),
                    icon: Building2,
                    shortcut: 'c',
                },
                {
                    title: 'Contacts',
                    href: contactsIndex(),
                    icon: Users,
                    shortcut: 'o',
                },
                {
                    title: 'Invoices',
                    href: invoicesIndex(),
                    icon: Receipt,
                    shortcut: 'v',
                },
            ],
        },
    ];
}

/**
 * The links pinned to the bottom of the sidebar.
 */
export function footerNavigation(): NavItem[] {
    return [
        {
            title: 'Design system',
            href: designSystem(),
            icon: Palette,
            shortcut: 'd',
        },
        {
            title: 'Settings',
            href: profileEdit(),
            icon: Settings,
            shortcut: 's',
        },
    ];
}

/**
 * Every destination with a keyboard shortcut, flattened.
 */
export function shortcutDestinations(): NavItem[] {
    return [
        ...workspaceNavigation().flatMap((section) => section.items),
        ...footerNavigation(),
    ].filter((item) => item.shortcut);
}
