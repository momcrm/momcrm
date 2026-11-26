import { usePage } from '@inertiajs/react';
import {
    Activity,
    Bell,
    BookOpen,
    Boxes,
    Building2,
    Clock,
    CreditCard,
    FileCog,
    KeyRound,
    Landmark,
    Mail,
    Package,
    Palette,
    Plug,
    Puzzle,
    Receipt,
    Shield,
    UserRound,
    UsersRound,
    Workflow,
} from 'lucide-react';
import type { NavSection } from '@/lib/workspace-navigation';
import { index as accessRulesIndex } from '@/routes/access-rules';
import { index as activityIndex } from '@/routes/activity';
import { edit as editAppearance } from '@/routes/appearance';
import { index as articleCategoriesIndex } from '@/routes/article-categories';
import { edit as editAttendanceSettings } from '@/routes/attendance-settings';
import { index as bankAccountsIndex } from '@/routes/bank-accounts';
import { edit as editBilling } from '@/routes/billing';
import { index as connectorsIndex } from '@/routes/connectors';
import { edit as editCustomObjects } from '@/routes/custom-objects';
import { edit as editEnvironment } from '@/routes/environment';
import { index as mailboxesIndex } from '@/routes/mailboxes';
import { edit as editNotificationSettings } from '@/routes/notification-settings';
import { index as productsIndex } from '@/routes/products';
import { edit as editProfile } from '@/routes/profile';
import { index as ratesIndex } from '@/routes/rates';
import { edit as editSecurity } from '@/routes/security';
import { edit as editTeam } from '@/routes/team';
import { edit as editWorkflow } from '@/routes/workflow';
import type { NavItem } from '@/types';

/**
 * The icons a package may ask for by name for its settings page. Any other
 * name, or none, gets the puzzle piece.
 */
const packageIcons: Record<string, NavItem['icon']> = {
    'credit-card': CreditCard,
    'users-round': UsersRound,
};

/**
 * Whether a path is one of the Settings pages, which swap the sidebar for
 * the Settings menu.
 */
export function isSettingsUrl(path: string): boolean {
    return path === '/settings' || path.startsWith('/settings/');
}

/**
 * The Settings menu: your own pages first, then the team's, grouped by what
 * they set up. Someone only sees the pages they may open, and a group with
 * none of them is left out.
 */
export function useSettingsNavigation(): NavSection[] {
    const {
        canManageAccessRules,
        canManageAttendance,
        canManageBankFeeds,
        canManageBilling,
        canManageConnectors,
        canManageCustomObjects,
        canManageEmail,
        canManageEnvironment,
        canManageRates,
        canManageWorkflow,
        settingsNavigation = [],
    } = usePage().props;

    const item = (
        isVisible: boolean | undefined,
        navItem: NavItem,
    ): NavItem[] => (isVisible ? [navItem] : []);

    const appSections: NavSection[] = [
        {
            title: 'Account',
            items: [
                { title: 'Profile', href: editProfile(), icon: UserRound },
                { title: 'Security', href: editSecurity(), icon: Shield },
                { title: 'Activity', href: activityIndex(), icon: Activity },
            ],
        },
        {
            title: 'Preferences',
            items: [
                {
                    title: 'Notifications',
                    href: editNotificationSettings(),
                    icon: Bell,
                },
                { title: 'Appearance', href: editAppearance(), icon: Palette },
            ],
        },
        {
            title: 'Workspace',
            items: [
                { title: 'Team', href: editTeam(), icon: UsersRound },
                ...item(canManageAccessRules, {
                    title: 'Access rules',
                    href: accessRulesIndex(),
                    icon: KeyRound,
                }),
            ],
        },
        {
            title: 'Work',
            items: [
                ...item(canManageWorkflow, {
                    title: 'Workflow',
                    href: editWorkflow(),
                    icon: Workflow,
                }),
                ...item(canManageCustomObjects, {
                    title: 'Objects',
                    href: editCustomObjects(),
                    icon: Boxes,
                }),
                ...item(canManageWorkflow, {
                    title: 'Knowledge base',
                    href: articleCategoriesIndex(),
                    icon: BookOpen,
                }),
                ...item(canManageAttendance, {
                    title: 'Attendance',
                    href: editAttendanceSettings(),
                    icon: Clock,
                }),
            ],
        },
        {
            title: 'Billing',
            items: [
                ...item(canManageBilling, {
                    title: 'Billing details',
                    href: editBilling(),
                    icon: Building2,
                }),
                ...item(canManageRates, {
                    title: 'Rates',
                    href: ratesIndex(),
                    icon: Receipt,
                }),
                ...item(canManageRates, {
                    title: 'Products',
                    href: productsIndex(),
                    icon: Package,
                }),
                ...item(canManageBankFeeds, {
                    title: 'Bank feeds',
                    href: bankAccountsIndex(),
                    icon: Landmark,
                }),
            ],
        },
        {
            title: 'Channels',
            items: [
                ...item(canManageEmail, {
                    title: 'Email',
                    href: mailboxesIndex(),
                    icon: Mail,
                }),
                ...item(canManageConnectors, {
                    title: 'Connectors',
                    href: connectorsIndex(),
                    icon: Plug,
                }),
            ],
        },
        {
            // The .env belongs to the whole installation, not to a team.
            title: 'Installation',
            items: item(canManageEnvironment, {
                title: 'Environment',
                href: editEnvironment(),
                icon: FileCog,
            }),
        },
    ];

    // Packages add their own pages: to the section they name, or a new one.
    return settingsNavigation
        .reduce((all, { section, title, href, icon }) => {
            const packageItem: NavItem = {
                title,
                href,
                icon: packageIcons[icon ?? ''] ?? Puzzle,
            };

            return all.some((existing) => existing.title === section)
                ? all.map((existing) =>
                      existing.title === section
                          ? {
                                ...existing,
                                items: [...existing.items, packageItem],
                            }
                          : existing,
                  )
                : [...all, { title: section, items: [packageItem] }];
        }, appSections)
        .filter((section) => section.items.length > 0);
}
