import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { activity, overview } from '@/routes';

/**
 * Switches the Overview between how the workspace is doing and what the team
 * has been doing in it.
 */
export default function OverviewViews({
    current,
}: {
    current: 'summary' | 'activity';
}) {
    const views = [
        { value: 'summary', label: 'Summary', href: overview() },
        { value: 'activity', label: 'Activity', href: activity() },
    ] as const;

    return (
        <div className="border-b">
            <nav aria-label="Overview" className="-mb-px flex gap-4">
                {views.map((view) => (
                    <Link
                        key={view.value}
                        href={view.href}
                        aria-current={
                            current === view.value ? 'page' : undefined
                        }
                        className={cn(
                            'border-b-2 pb-2 text-sm',
                            current === view.value
                                ? 'border-foreground font-medium text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {view.label}
                    </Link>
                ))}
            </nav>
        </div>
    );
}
