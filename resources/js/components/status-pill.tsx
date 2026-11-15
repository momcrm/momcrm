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
 * The dot takes a tone, or a class such as a team choice's colour.
 */
export default function StatusPill({
    tone = 'neutral',
    dotClassName,
    className,
    children,
}: {
    tone?: Tone;
    dotClassName?: string;
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
                className={cn(
                    'size-1.5 rounded-full',
                    dotClassName ?? dotColors[tone],
                )}
            />
            {children}
        </Badge>
    );
}
