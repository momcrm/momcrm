import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { leave, index as today, time, timesheets } from '@/routes/attendance';

/**
 * Switches between the Today board, a member's week, leave and timesheets,
 * with the page's own actions at the right of the row.
 */
export default function AttendanceTabs({
    current,
    actions,
}: {
    /** The tab showing, or null for someone else's week. */
    current: 'today' | 'time' | 'leave' | 'timesheets' | null;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-end gap-x-4 gap-y-2 border-b">
            <nav aria-label="Attendance" className="flex gap-4 text-sm">
                {(
                    [
                        ['today', 'Today', today()],
                        ['time', 'My time', time()],
                        ['leave', 'Leave', leave()],
                        ['timesheets', 'Timesheets', timesheets()],
                    ] as const
                ).map(([value, label, href]) => (
                    <Link
                        key={value}
                        href={href}
                        aria-current={current === value ? 'page' : undefined}
                        className={cn(
                            '-mb-px border-b-2 pb-2',
                            current === value
                                ? 'border-foreground font-medium'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {label}
                    </Link>
                ))}
            </nav>
            {actions && (
                <div className="ml-auto flex items-center gap-2 pb-2">
                    {actions}
                </div>
            )}
        </div>
    );
}
