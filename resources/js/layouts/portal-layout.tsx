import { Link, router, usePage } from '@inertiajs/react';
import { ArrowLeftRight, LogOut } from 'lucide-react';
import type { ReactNode } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { choose, home, logout, statement } from '@/routes/portal';
import { index as articles } from '@/routes/portal/articles';
import { index as assets } from '@/routes/portal/assets';
import { index as invoices } from '@/routes/portal/invoices';
import { index as receipts } from '@/routes/portal/receipts';
import { index as services } from '@/routes/portal/services';
import { index as tickets } from '@/routes/portal/tickets';
import type { PortalSection, PortalShared } from '@/types';

/**
 * The signed-in contact and what they may see, on any portal page.
 */
export function usePortal(): PortalShared {
    const portal = usePage().props.portal;

    if (!portal) {
        throw new Error('usePortal() is only for customer portal pages.');
    }

    return portal;
}

type PortalNavItem = {
    title: string;
    href: string;
    /** The section it shows; none for pages every contact has. */
    section: PortalSection | null;
};

/**
 * The portal's sections, in the order the header lists them. Each shows only
 * when the access rules let the contact see some of it.
 */
export const portalNavItems: PortalNavItem[] = [
    { title: 'Home', href: home.url(), section: null },
    { title: 'Tickets', href: tickets.url(), section: 'tickets' },
    { title: 'Invoices', href: invoices.url(), section: 'invoices' },
    { title: 'Receipts', href: receipts.url(), section: 'receipts' },
    { title: 'Statement', href: statement.url(), section: 'statement' },
    { title: 'Services', href: services.url(), section: 'services' },
    { title: 'Assets', href: assets.url(), section: 'assets' },
    { title: 'Knowledge base', href: articles.url(), section: 'articles' },
];

/**
 * The customer portal: the provider's name, the sections the contact may
 * see, and who they are signed in as, above a centred page.
 */
export default function PortalLayout({ children }: { children: ReactNode }) {
    const portal = usePortal();
    const getInitials = useInitials();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const items = portalNavItems.filter(
        (item) => item.section === null || portal.sections[item.section],
    );

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b bg-sidebar">
                <div className="mx-auto flex h-14 w-full max-w-5xl items-center gap-4 px-4">
                    <Link
                        href={home()}
                        className="min-w-0 shrink-0 truncate font-semibold"
                    >
                        {portal.team.name}
                    </Link>

                    <nav className="-mx-1 flex min-w-0 flex-1 gap-1 overflow-x-auto">
                        {items.map((item) => {
                            const isActive =
                                item.href === home.url()
                                    ? isCurrentUrl(item.href)
                                    : isCurrentOrParentUrl(item.href);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={cn(
                                        'shrink-0 rounded-md px-2.5 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-foreground',
                                        isActive &&
                                            'bg-accent font-medium text-foreground',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            );
                        })}
                    </nav>

                    <DropdownMenu>
                        <DropdownMenuTrigger
                            className="flex shrink-0 items-center gap-2 rounded-md p-1 hover:bg-accent"
                            aria-label="Your account"
                        >
                            <span className="hidden text-right text-xs leading-tight sm:block">
                                <span className="block font-medium">
                                    {portal.contact.name}
                                </span>
                                <span className="block text-muted-foreground">
                                    {portal.customer.name}
                                </span>
                            </span>
                            <Avatar className="size-8">
                                <AvatarFallback className="text-xs">
                                    {getInitials(portal.contact.name)}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-60">
                            <DropdownMenuLabel className="font-normal">
                                <span className="block truncate text-sm font-medium">
                                    {portal.contact.name}
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    {portal.contact.email}
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    {portal.customer.name}
                                </span>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {portal.canSwitch && (
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={choose()}
                                        className="w-full cursor-pointer"
                                    >
                                        <ArrowLeftRight className="mr-2" />
                                        Switch customer
                                    </Link>
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuItem
                                className="cursor-pointer"
                                onSelect={() => {
                                    router.flushAll();
                                    router.post(logout.url());
                                }}
                            >
                                <LogOut className="mr-2" />
                                Sign out
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
