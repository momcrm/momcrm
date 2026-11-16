import * as React from 'react';

import { cn } from '@/lib/utils';

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            data-slot="textarea"
            className={cn(
                'flex field-sizing-content min-h-18 w-full rounded-md border px-2.5 py-2 text-sm leading-normal border-border-strong bg-card shadow-none transition-[color,border-color,box-shadow] duration-150 outline-none placeholder:text-muted-foreground hover:border-[color-mix(in_srgb,var(--border-strong)_60%,var(--muted-foreground))] focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive aria-invalid:ring-[3px] aria-invalid:ring-destructive/10',
                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
