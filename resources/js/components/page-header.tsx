import type { ReactNode } from 'react';

/**
 * The title row of a list or detail page, with its main actions on the right.
 */
export default function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 className="text-[23px] leading-[1.2] font-semibold tracking-[-0.022em] text-balance">
                    {title}
                </h1>
                {description && (
                    <div className="mt-1 text-[13.5px] text-soft-foreground">
                        {description}
                    </div>
                )}
            </div>
            {actions && (
                <div className="ml-auto flex flex-wrap items-center gap-1.5">
                    {actions}
                </div>
            )}
        </div>
    );
}
