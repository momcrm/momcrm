import { Link, usePage } from '@inertiajs/react';
import {
    Bell as BellIcon,
    Building2,
    ChevronDown,
    CircleDashed,
    FolderKanban,
    Plus,
    Receipt,
    Search,
    UserPlus,
    UserRound,
} from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { useCommandPalette } from '@/components/command-palette';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import { useInitials } from '@/hooks/use-initials';
import { create as createContact } from '@/routes/contacts';
import { create as createInvoice } from '@/routes/invoices';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as projectsIndex } from '@/routes/projects';
import { edit as teamEdit } from '@/routes/team';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iP(hone|ad)/.test(navigator.platform);

function NewMenu() {
    const { openCreateTicket } = useCreateTicket();
    const { openCreateCustomer } = useCreateCustomer();
    const items = [
        {
            label: 'Project',
            icon: FolderKanban,
            href: projectsIndex({ query: { new: 1 } }),
        },
        { label: 'Contact', icon: UserRound, href: createContact() },
        { label: 'Invoice', icon: Receipt, href: createInvoice() },
    ];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="h-7 gap-1 px-2">
                    <Plus />
                    New
                    <ChevronDown className="size-3.5! text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                    Create
                </DropdownMenuLabel>
                <DropdownMenuItem onSelect={() => openCreateTicket()}>
                    <CircleDashed />
                    Ticket
                </DropdownMenuItem>
                <DropdownMenuItem onSelect={() => openCreateCustomer()}>
                    <Building2 />
                    Customer
                </DropdownMenuItem>
                {items.map((item) => (
                    <DropdownMenuItem key={item.label} asChild>
                        <Link href={item.href}>
                            <item.icon />
                            {item.label}
                        </Link>
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={teamEdit()}>
                        <UserPlus />
                        Invite member
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Bell() {
    const { sidebarCounts } = usePage().props;
    const unread = sidebarCounts?.unreadNotifications ?? 0;

    return (
        <Button variant="ghost" size="icon" className="relative size-7" asChild>
            <Link
                href={notificationsIndex()}
                aria-label={
                    unread > 0
                        ? `Notifications, ${unread} unread`
                        : 'Notifications'
                }
            >
                <BellIcon />
                {unread > 0 && (
                    <span className="absolute top-1 right-1 size-2 rounded-full bg-primary ring-2 ring-background" />
                )}
            </Link>
        </Button>
    );
}

function AccountMenu() {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    if (!auth.user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                aria-label="Account menu"
            >
                <Avatar className="size-7">
                    <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                    <AvatarFallback className="bg-accent text-[11px] font-medium">
                        {getInitials(auth.user.name)}
                    </AvatarFallback>
                </Avatar>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * The bar above every page: where you are on the left, and search, create
 * and your account on the right.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { open: openPalette } = useCommandPalette();
    const { state, isMobile } = useSidebar();

    return (
        <header className="sticky top-0 z-20 flex h-[46px] shrink-0 items-center gap-2 border-b bg-background/90 px-4 backdrop-blur">
            {(state === 'collapsed' || isMobile) && (
                <SidebarTrigger className="-ml-1 size-7" />
            )}
            <Breadcrumbs breadcrumbs={breadcrumbs} />

            <div className="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    onClick={openPalette}
                    className="flex h-7 items-center gap-2 rounded-md border bg-card px-2 text-sm text-subtle-foreground shadow-xs transition-colors hover:text-muted-foreground md:w-56"
                >
                    <Search className="size-3.5" />
                    <span className="hidden md:inline">Search or jump to…</span>
                    <kbd className="ml-auto hidden rounded-sm border bg-muted px-1 font-mono text-[10px] md:inline">
                        {isMac ? '⌘K' : 'Ctrl K'}
                    </kbd>
                </button>
                <Bell />
                <NewMenu />
                <AccountMenu />
            </div>
        </header>
    );
}
