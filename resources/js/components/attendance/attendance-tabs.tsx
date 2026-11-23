import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { leave, index as today, time, timesheets } from '@/routes/attendance';

/**
 * Switches between the Today board, a member's week, leave and timesheets.
 */
export default function AttendanceTabs({
    current,
}: {
    /** The tab showing, or null for someone else's week. */
    current: 'today' | 'time' | 'leave' | 'timesheets' | null;
}) {
    return (
        <nav aria-label="Attendance" className="flex gap-4 border-b text-sm">
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
    );
}
