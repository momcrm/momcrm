import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * What a list shows before it has anything in it: what goes here and why.
 * `compact` drops the dashed outline and padding, for lists inside a panel.
 */
export default function EmptyState({
    title,
    description,
    action,
    icon: Icon,
    compact = false,
}: {
    title: string;
    description: string;
    action?: ReactNode;
    icon?: LucideIcon;
    compact?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center text-center',
                compact
                    ? 'gap-1.5 px-4 py-7'
                    : 'gap-3 rounded-lg border border-dashed px-6 py-12',
            )}
        >
            {Icon && (
                <Icon
                    aria-hidden
                    className="mb-1 size-5.5 text-subtle-foreground"
                />
            )}
            <h2 className="font-medium">{title}</h2>
            <p className="max-w-sm text-sm text-muted-foreground">
                {description}
            </p>
            {action}
        </div>
    );
}
