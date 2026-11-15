import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { hueStyle } from '@/lib/colors';
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
 * The dot takes a tone, or a hue such as a team choice's colour.
 */
export default function StatusPill({
    tone = 'neutral',
    hue,
    className,
    children,
}: {
    tone?: Tone;
    /** A stored hue (null for grey); when given, it colours the dot instead of the tone. */
    hue?: number | null;
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
                    hue === undefined ? dotColors[tone] : 'hue-dot',
                )}
                style={hue === undefined ? undefined : hueStyle(hue)}
            />
            {children}
        </Badge>
    );
}
