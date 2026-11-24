import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
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
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { index as accessRulesIndex } from '@/routes/access-rules';
import { index as articleCategoriesIndex } from '@/routes/article-categories';
import { edit as editAttendanceSettings } from '@/routes/attendance-settings';
import { edit as editBilling } from '@/routes/billing';
import { index as activityIndex } from '@/routes/activity';
import { edit as editAppearance } from '@/routes/appearance';
import { index as bankAccountsIndex } from '@/routes/bank-accounts';
import { index as connectorsIndex } from '@/routes/connectors';
import { edit as editCustomObjects } from '@/routes/custom-objects';
import { edit as editEnvironment } from '@/routes/environment';
import { index as mailboxesIndex } from '@/routes/mailboxes';
import { index as productsIndex } from '@/routes/products';
import { edit } from '@/routes/profile';
import { index as ratesIndex } from '@/routes/rates';
import { edit as editSecurity } from '@/routes/security';
import { edit as editTeam } from '@/routes/team';
import { edit as editWorkflow } from '@/routes/workflow';
import type { NavItem } from '@/types';

const knowledgeBaseItem: NavItem = {
    title: 'Knowledge base',
    href: articleCategoriesIndex(),
    icon: BookOpen,
};

const attendanceItem: NavItem = {
    title: 'Attendance',
    href: editAttendanceSettings(),
    icon: Clock,
};

const accessRulesItem: NavItem = {
    title: 'Access rules',
    href: accessRulesIndex(),
    icon: KeyRound,
};

const workflowItem: NavItem = {
    title: 'Workflow',
    href: editWorkflow(),
    icon: Workflow,
};

const customObjectsItem: NavItem = {
    title: 'Objects',
    href: editCustomObjects(),
    icon: Boxes,
};

const connectorsItem: NavItem = {
    title: 'Connectors',
    href: connectorsIndex(),
    icon: Plug,
};

const billingItem: NavItem = {
    title: 'Billing details',
    href: editBilling(),
    icon: Building2,
};

const productsItem: NavItem = {
    title: 'Products',
    href: productsIndex(),
    icon: Package,
};

const bankFeedsItem: NavItem = {
    title: 'Bank feeds',
    href: bankAccountsIndex(),
    icon: Landmark,
};

const ratesItem: NavItem = {
    title: 'Rates',
    href: ratesIndex(),
    icon: Receipt,
};

const emailItem: NavItem = {
    title: 'Email',
    href: mailboxesIndex(),
    icon: Mail,
};

const environmentItem: NavItem = {
    title: 'Environment',
    href: editEnvironment(),
    icon: FileCog,
};

/**
 * The icons a package may ask for by name for its settings page. Any other
 * name, or none, gets the puzzle piece.
 */
const packageIcons: Record<string, NavItem['icon']> = {
    'credit-card': CreditCard,
    'users-round': UsersRound,
};

const sections: { title: string; items: NavItem[] }[] = [
    {
        title: 'Account',
        items: [
            { title: 'Profile', href: edit(), icon: UserRound },
            { title: 'Security', href: editSecurity(), icon: Shield },
            { title: 'Activity', href: activityIndex(), icon: Activity },
        ],
    },
    {
        title: 'Workspace',
        items: [{ title: 'Team', href: editTeam(), icon: UsersRound }],
    },
    {
        title: 'Preferences',
        items: [{ title: 'Appearance', href: editAppearance(), icon: Palette }],
    },
];

/**
 * Settings pages share a grouped second-level menu on the left. Most are
 * forms and keep to a narrow column; a page that lays things out side by
 * side asks for a wide one with `setLayoutProps({ wide: true })`.
 */
export default function SettingsLayout({
    wide = false,
    children,
}: PropsWithChildren<{ wide?: boolean }>) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
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
    const appSections = [
        ...sections,
        // The .env belongs to the whole installation, not to a team.
        ...(canManageEnvironment
            ? [{ title: 'Installation', items: [environmentItem] }]
            : []),
    ].map((section) =>
        section.title === 'Workspace'
            ? {
                  ...section,
                  items: [
                      ...section.items,
                      ...(canManageWorkflow
                          ? [workflowItem, knowledgeBaseItem]
                          : []),
                      ...(canManageCustomObjects ? [customObjectsItem] : []),
                      ...(canManageAttendance ? [attendanceItem] : []),
                      ...(canManageBilling ? [billingItem] : []),
                      ...(canManageRates ? [ratesItem, productsItem] : []),
                      ...(canManageBankFeeds ? [bankFeedsItem] : []),
                      ...(canManageEmail ? [emailItem] : []),
                      ...(canManageConnectors ? [connectorsItem] : []),
                      ...(canManageAccessRules ? [accessRulesItem] : []),
                  ],
              }
            : section,
    );
    // Packages add their own pages: to the section they name, or a new one.
    const visibleSections = settingsNavigation.reduce(
        (all, { section, title, href, icon }) => {
            const item = {
                title,
                href,
                icon: packageIcons[icon ?? ''] ?? Puzzle,
            };

            return all.some((existing) => existing.title === section)
                ? all.map((existing) =>
                      existing.title === section
                          ? { ...existing, items: [...existing.items, item] }
                          : existing,
                  )
                : [...all, { title: section, items: [item] }];
        },
        appSections,
    );

    return (
        <div className="flex flex-col gap-6 p-4 md:flex-row md:gap-10 md:p-6">
            <aside className="w-full shrink-0 md:w-52">
                <h1 className="mb-4 text-xl font-semibold">Settings</h1>
                <nav aria-label="Settings" className="flex flex-col gap-4">
                    {visibleSections.map((section) => (
                        <div key={section.title} className="space-y-0.5">
                            <h2 className="px-2 pb-1 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                {section.title}
                            </h2>
                            {section.items.map((item) => {
                                const isActive = isCurrentOrParentUrl(
                                    item.href,
                                );

                                return (
                                    <Link
                                        key={toUrl(item.href)}
                                        href={item.href}
                                        className={cn(
                                            'flex h-8 items-center gap-2.5 rounded-md px-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground',
                                            isActive &&
                                                'bg-card font-medium text-foreground shadow-card',
                                        )}
                                    >
                                        {item.icon && (
                                            <item.icon className="size-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                );
                            })}
                        </div>
                    ))}
                </nav>
            </aside>

            <div
                className={cn(
                    'min-w-0 flex-1',
                    wide ? 'md:max-w-4xl' : 'md:max-w-2xl',
                )}
            >
                <section className={cn('space-y-12', !wide && 'max-w-xl')}>
                    {children}
                </section>
            </div>
        </div>
    );
}
