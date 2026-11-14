import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Tone } from '@/types';

const toneColors: Record<Tone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    primary: 'bg-primary/10 text-primary',
    info: 'bg-info/10 text-info',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/12 text-warning',
    destructive: 'bg-destructive/10 text-destructive',
};

/**
 * A short label on a lightly tinted background, e.g. "Paid" or "Overdue".
 */
export default function SoftBadge({
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
            className={cn('border-transparent', toneColors[tone], className)}
        >
            {children}
        </Badge>
    );
}
