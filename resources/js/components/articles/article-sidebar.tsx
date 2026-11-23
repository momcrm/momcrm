import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * The column beside an article's body, on its page and in the editor. On
 * wide screens it stays in view below the app header (46px high) and scrolls
 * on its own when it is taller than the window.
 */
export function ArticleSidebar({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <aside
            className={cn(
                'space-y-4 lg:sticky lg:top-[calc(46px+1.5rem)] lg:-m-1 lg:max-h-[calc(100svh-46px-3rem)] lg:[scrollbar-width:thin] lg:self-start lg:overflow-y-auto lg:p-1',
                className,
            )}
        >
            {children}
        </aside>
    );
}

/** A titled card in the article sidebar. */
export function SidePanel({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-xl border bg-card shadow-xs">
            <h2 className="flex h-10 items-center px-4 text-[13px] font-medium">
                {title}
            </h2>
            {children}
        </section>
    );
}
