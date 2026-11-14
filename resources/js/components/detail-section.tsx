import type { ReactNode } from 'react';

/**
 * A titled block on a detail page, such as a customer's contacts or tickets.
 */
export default function DetailSection({
    title,
    action,
    children,
}: {
    title: string;
    action?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-medium">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}
