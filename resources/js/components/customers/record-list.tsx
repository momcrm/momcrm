import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type RecordListItem = {
    id: string;
    href: string;
    title: ReactNode;
    detail?: ReactNode;
    badge?: ReactNode;
};

/**
 * A bordered list of linked rows, used for a customer's locations, assets and services.
 */
export default function RecordList({ items }: { items: RecordListItem[] }) {
    return (
        <ul className="divide-y rounded-lg border">
            {items.map((item) => (
                <li key={item.id}>
                    <Link
                        href={item.href}
                        className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
                    >
                        <div className="min-w-0">
                            <p className="truncate font-medium">{item.title}</p>
                            {item.detail && (
                                <p className="truncate text-sm text-muted-foreground">
                                    {item.detail}
                                </p>
                            )}
                        </div>
                        {item.badge}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
