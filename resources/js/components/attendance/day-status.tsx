import StatusPill from '@/components/status-pill';
import { dayStatuses, formatDuration } from '@/lib/attendance';
import type { AttendanceDayResult } from '@/types';

/**
 * How a day went against the member's schedule, as pills: its status (late,
 * absent, a holiday…) and any overtime. A day still to come, or a team with
 * no schedules, shows nothing.
 */
export default function DayStatus({
    day,
    showOnTime = true,
}: {
    day: AttendanceDayResult;
    /** Whether a day going to plan gets an "On time" pill too. */
    showOnTime?: boolean;
}) {
    const status = dayStatuses[day.status];
    const showStatus =
        day.status !== 'upcoming' &&
        day.status !== 'unscheduled' &&
        (showOnTime || day.status !== 'present');
    const overtime = day.flags.includes('overtime');

    if (!showStatus && !overtime) {
        return null;
    }

    return (
        <span className="flex flex-wrap items-center gap-1.5">
            {showStatus && (
                <StatusPill tone={status.tone}>
                    {day.status === 'holiday' && day.holiday
                        ? day.holiday
                        : day.status === 'late' && day.late_seconds > 0
                          ? `Late ${formatDuration(day.late_seconds)}`
                          : status.label}
                </StatusPill>
            )}
            {day.flags.includes('left_early') &&
                day.status !== 'left_early' && (
                    <StatusPill tone="warning">Left early</StatusPill>
                )}
            {overtime && (
                <StatusPill tone="info">
                    +{formatDuration(day.overtime_seconds)} overtime
                </StatusPill>
            )}
        </span>
    );
}
