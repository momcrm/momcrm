import { useEffect, useState } from 'react';
import type {
    AttendanceDayStatus,
    Tone,
    WeekdayKey,
    WorkScheduleDay,
} from '@/types';

/**
 * A length of time worked, such as "7h 30m", "45m" or "0m".
 */
export function formatDuration(seconds: number): string {
    const minutes = Math.max(0, Math.floor(seconds / 60));
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest}m`;
    }

    return rest === 0 ? `${hours}h` : `${hours}h ${rest}m`;
}

/**
 * The time of day of an instant, such as "9:02 am". Give a timezone to read
 * it as a member far away would, instead of in the browser's own.
 */
export function formatClockTime(iso: string, timeZone?: string): string {
    return new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        timeZone,
    }).format(new Date(iso));
}

/**
 * Seconds from an instant until now.
 */
export function secondsSince(iso: string, now: number): number {
    return Math.max(0, Math.floor((now - new Date(iso).getTime()) / 1000));
}

/**
 * The current time, updated every interval while `active`, so running
 * timers tick.
 */
export function useNow(active = true, intervalMs = 15_000): number {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (!active) {
            return;
        }

        setNow(Date.now());
        const timer = window.setInterval(() => setNow(Date.now()), intervalMs);

        return () => window.clearInterval(timer);
    }, [active, intervalMs]);

    return now;
}

/**
 * The browser's timezone, sent on clock-in for members who have not set one.
 */
export function browserTimezone(): string | undefined {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch {
        return undefined;
    }
}

export const weekdays: { key: WeekdayKey; short: string; long: string }[] = [
    { key: 'mon', short: 'Mon', long: 'Monday' },
    { key: 'tue', short: 'Tue', long: 'Tuesday' },
    { key: 'wed', short: 'Wed', long: 'Wednesday' },
    { key: 'thu', short: 'Thu', long: 'Thursday' },
    { key: 'fri', short: 'Fri', long: 'Friday' },
    { key: 'sat', short: 'Sat', long: 'Saturday' },
    { key: 'sun', short: 'Sun', long: 'Sunday' },
];

/** What each day status reads as, and its colour. */
export const dayStatuses: Record<
    AttendanceDayStatus,
    { label: string; tone: Tone }
> = {
    present: { label: 'On time', tone: 'success' },
    late: { label: 'Late', tone: 'warning' },
    left_early: { label: 'Left early', tone: 'warning' },
    absent: { label: 'Absent', tone: 'destructive' },
    upcoming: { label: 'Scheduled', tone: 'neutral' },
    holiday: { label: 'Holiday', tone: 'info' },
    day_off: { label: 'Day off', tone: 'neutral' },
    unscheduled: { label: '', tone: 'neutral' },
};

/**
 * Minutes from a day's start to its end, running past midnight when the end
 * is earlier, less the break.
 */
export function scheduledMinutes(day: WorkScheduleDay): number {
    const minutes = (time: string) => {
        const [hours, rest] = time.split(':').map(Number);

        return hours * 60 + rest;
    };
    let span = minutes(day.ends_at) - minutes(day.starts_at);

    if (span <= 0) {
        span += 24 * 60;
    }

    return Math.max(0, span - day.break_minutes);
}

/**
 * A schedule's week in a line, grouping weekdays with the same hours:
 * "Mon–Fri 09:00–17:00 · Sat 10:00–14:00".
 */
export function describeWeek(
    days: Record<WeekdayKey, WorkScheduleDay | null>,
): string {
    const hours = (day: WorkScheduleDay | null) =>
        day ? `${day.starts_at}–${day.ends_at}` : null;
    const groups: { from: number; to: number; hours: string }[] = [];

    weekdays.forEach(({ key }, index) => {
        const text = hours(days[key]);
        const last = groups[groups.length - 1];

        if (text === null) {
            return;
        }

        if (last && last.to === index - 1 && last.hours === text) {
            last.to = index;
        } else {
            groups.push({ from: index, to: index, hours: text });
        }
    });

    if (groups.length === 0) {
        return 'No working days';
    }

    return groups
        .map(
            (group) =>
                `${weekdays[group.from].short}${group.to > group.from ? `–${weekdays[group.to].short}` : ''} ${group.hours}`,
        )
        .join(' · ');
}
