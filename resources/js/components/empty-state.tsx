import type { ReactNode } from 'react';

/**
 * What a list shows before it has anything in it: what goes here and why.
 */
export default function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed px-6 py-12 text-center">
            <h2 className="font-medium">{title}</h2>
            <p className="max-w-sm text-sm text-muted-foreground">
                {description}
            </p>
            {action}
        </div>
    );
}
