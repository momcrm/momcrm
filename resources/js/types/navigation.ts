import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** A count shown on the right, e.g. open tickets. Hidden when 0. */
    badge?: number | null;
    /** The key pressed after "G" to jump here, e.g. "t" for G then T. */
    shortcut?: string;
};
