import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * A native <select> styled like the Input component, so it submits with
 * Inertia's <Form> without extra state. It draws its own chevron, inset like
 * the text, instead of the browser's arrow.
 */
export default function NativeSelect({
    className,
    ...props
}: ComponentProps<'select'>) {
    return (
        <select
            className={cn(
                'flex h-9 w-full select-chevron rounded-md border border-input bg-transparent bg-size-[1rem] bg-position-[right_0.75rem_center] py-1 pr-9 pl-3 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30',
                className,
            )}
            {...props}
        />
    );
}
