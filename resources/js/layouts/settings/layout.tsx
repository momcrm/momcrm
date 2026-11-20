import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Boxes,
    Building2,
    FileCog,
    KeyRound,
    Mail,
    Palette,
    Plug,
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
import { edit as editBilling } from '@/routes/billing';
import { index as activityIndex } from '@/routes/activity';
import { edit as editAppearance } from '@/routes/appearance';
import { index as connectorsIndex } from '@/routes/connectors';
import { edit as editCustomObjects } from '@/routes/custom-objects';
import { edit as editEnvironment } from '@/routes/environment';
import { index as mailboxesIndex } from '@/routes/mailboxes';
import { edit } from '@/routes/profile';
import { index as ratesIndex } from '@/routes/rates';
import { edit as editSecurity } from '@/routes/security';
import { edit as editTeam } from '@/routes/team';
import { edit as editWorkflow } from '@/routes/workflow';
import type { NavItem } from '@/types';

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
        canManageBilling,
        canManageConnectors,
        canManageCustomObjects,
        canManageEmail,
        canManageEnvironment,
        canManageRates,
        canManageWorkflow,
    } = usePage().props;
    const visibleSections = [
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
                      ...(canManageWorkflow ? [workflowItem] : []),
                      ...(canManageCustomObjects ? [customObjectsItem] : []),
                      ...(canManageBilling ? [billingItem] : []),
                      ...(canManageRates ? [ratesItem] : []),
                      ...(canManageEmail ? [emailItem] : []),
                      ...(canManageConnectors ? [connectorsItem] : []),
                      ...(canManageAccessRules ? [accessRulesItem] : []),
                  ],
              }
            : section,
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
