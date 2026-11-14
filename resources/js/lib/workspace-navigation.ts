import {
    Building2,
    CircleUser,
    Home,
    Palette,
    Receipt,
    Settings,
    Ticket,
    Users,
} from 'lucide-react';
import { dashboard, designSystem } from '@/routes';
import { index as contactsIndex } from '@/routes/contacts';
import { index as customersIndex } from '@/routes/customers';
import { index as invoicesIndex } from '@/routes/invoices';
import { edit as profileEdit } from '@/routes/profile';
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
                    title: 'My tickets',
                    href: myTickets(),
                    icon: CircleUser,
                    badge: counts?.myOpenTickets,
                    shortcut: 'm',
                },
            ],
        },
        {
            title: 'Workspace',
            items: [
                {
                    title: 'Tickets',
                    href: ticketsIndex(),
                    icon: Ticket,
                    badge: counts?.openTickets,
                    shortcut: 't',
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
