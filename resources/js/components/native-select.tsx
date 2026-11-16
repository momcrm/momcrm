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
                'flex h-control-md w-full cursor-pointer select-chevron rounded-md border border-border-strong bg-card bg-size-[0.75rem] bg-position-[right_0.5625rem_center] py-0 pr-7 pl-2.5 text-sm shadow-none transition-[color,border-color,box-shadow] duration-150 outline-none placeholder:text-muted-foreground hover:border-[color-mix(in_srgb,var(--border-strong)_60%,var(--muted-foreground))] focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive aria-invalid:ring-[3px] aria-invalid:ring-destructive/10',
                className,
            )}
            {...props}
        />
    );
}
