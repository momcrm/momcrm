import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Boxes,
    KeyRound,
    Mail,
    Palette,
    Shield,
    UserRound,
    UsersRound,
    Workflow,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { index as accessRulesIndex } from '@/routes/access-rules';
import { index as activityIndex } from '@/routes/activity';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editCustomObjects } from '@/routes/custom-objects';
import { index as mailboxesIndex } from '@/routes/mailboxes';
import { edit } from '@/routes/profile';
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

const emailItem: NavItem = {
    title: 'Email',
    href: mailboxesIndex(),
    icon: Mail,
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
 * Settings pages share a grouped second-level menu on the left.
 */
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const {
        canManageAccessRules,
        canManageCustomObjects,
        canManageEmail,
        canManageWorkflow,
    } = usePage().props;
    const visibleSections = sections.map((section) =>
        section.title === 'Workspace'
            ? {
                  ...section,
                  items: [
                      ...section.items,
                      ...(canManageWorkflow ? [workflowItem] : []),
                      ...(canManageCustomObjects ? [customObjectsItem] : []),
                      ...(canManageEmail ? [emailItem] : []),
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

            <div className="min-w-0 flex-1 md:max-w-2xl">
                <section className="max-w-xl space-y-12">{children}</section>
            </div>
        </div>
    );
}
