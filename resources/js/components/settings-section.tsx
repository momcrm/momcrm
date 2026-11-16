import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * A card of related settings with a title and a sentence on what they are
 * for, its rows divided by hairlines.
 */
export function SettingsSection({
    title,
    description,
    tone = 'default',
    children,
}: {
    title: string;
    description?: ReactNode;
    tone?: 'default' | 'destructive';
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-xl border bg-card shadow-xs',
                tone === 'destructive' && 'border-destructive/30',
            )}
        >
            <header
                className={cn(
                    'border-b bg-muted/40 px-4 py-3',
                    tone === 'destructive' &&
                        'border-destructive/20 bg-destructive/5',
                )}
            >
                <h3
                    className={cn(
                        'text-sm font-semibold',
                        tone === 'destructive' && 'text-destructive',
                    )}
                >
                    {title}
                </h3>
                {description && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </header>
            <div className="divide-y">{children}</div>
        </section>
    );
}

/**
 * One setting: what it is on the left, its control on the right, stacked on
 * narrow screens, as staff-vue's settings rows are.
 */
export function SettingsRow({
    label,
    htmlFor,
    description,
    error,
    children,
}: {
    label: string;
    htmlFor?: string;
    description?: ReactNode;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2.5 px-4 py-3.5 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:items-start sm:gap-8">
            <div className="min-w-0 sm:pt-1.5">
                {htmlFor ? (
                    <Label htmlFor={htmlFor} className="text-sm font-medium">
                        {label}
                    </Label>
                ) : (
                    <p className="text-sm font-medium">{label}</p>
                )}
                {description && (
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <div className="min-w-0">
                {children}
                <InputError message={error} className="mt-1.5" />
            </div>
        </div>
    );
}
