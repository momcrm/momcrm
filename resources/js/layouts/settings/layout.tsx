import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

/**
 * Settings pages take their menu from the sidebar, so this only sets the
 * column they sit in. Most are forms and keep to a narrow one; a page that
 * lays things out side by side asks for a wide one with
 * `setLayoutProps({ wide: true })`.
 */
export default function SettingsLayout({
    wide = false,
    children,
}: PropsWithChildren<{ wide?: boolean }>) {
    return (
        <div className="p-4 md:p-6">
            <section
                className={cn('space-y-12', wide ? 'max-w-4xl' : 'max-w-xl')}
            >
                {children}
            </section>
        </div>
    );
}
