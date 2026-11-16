import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

/**
 * The title row of a customer workspace tab, with its main action.
 */
export function TabHeader({
    title,
    description,
    action,
}: {
    title: string;
    description?: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 className="text-base font-semibold">{title}</h2>
                {description && (
                    <p className="text-[13px] text-soft-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action}
        </div>
    );
}

/**
 * A small outlined button that links to a create page.
 */
export function AddLink({ href, label }: { href: string; label: string }) {
    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href}>{label}</Link>
        </Button>
    );
}
