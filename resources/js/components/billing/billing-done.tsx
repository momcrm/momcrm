import { CircleCheck } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * The last step of a billing page: what was done, and what to do next.
 */
export default function BillingDone({
    title,
    children,
    actions,
}: {
    title: string;
    children?: ReactNode;
    actions: ReactNode;
}) {
    return (
        <div className="rounded-lg border border-l-[3px] border-l-success bg-card p-4">
            <div className="flex items-start gap-3">
                <CircleCheck className="mt-0.5 size-5 shrink-0 text-success" />
                <div className="min-w-0 flex-1 space-y-3">
                    <div>
                        <p className="text-sm font-medium">{title}</p>
                        {children && (
                            <div className="text-sm text-muted-foreground">
                                {children}
                            </div>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">{actions}</div>
                </div>
            </div>
        </div>
    );
}
