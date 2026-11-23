import { useEffect, useState } from 'react';

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
