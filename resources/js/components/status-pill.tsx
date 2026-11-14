import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Tone } from '@/types';

const dotColors: Record<Tone, string> = {
    neutral: 'bg-muted-foreground',
    primary: 'bg-primary',
    info: 'bg-info',
    success: 'bg-success',
    warning: 'bg-warning',
    destructive: 'bg-destructive',
};

/**
 * A record's state: a coloured dot on a neutral outline, for tables and rows.
 */
export default function StatusPill({
    tone,
    className,
    children,
}: {
    tone: Tone;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Badge
            variant="outline"
            className={cn('gap-1.5 bg-card font-normal', className)}
        >
            <span
                aria-hidden
                className={cn('size-1.5 rounded-full', dotColors[tone])}
            />
            {children}
        </Badge>
    );
}
