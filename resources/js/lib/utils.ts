import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

/**
 * tailwind-merge taught our own theme tokens (resources/css/app.css), so that
 * `text-ui` counts as a font size rather than a colour and `h-control-md`
 * gives way to a caller's `h-8`.
 */
const twMerge = extendTailwindMerge({
    extend: {
        theme: {
            text: ['2xs', 'ui', 'title'],
            spacing: ['control-xs', 'control-sm', 'control-md', 'control-lg'],
            shadow: ['card', 'pop', 'drag'],
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}
