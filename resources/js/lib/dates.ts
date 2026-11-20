/**
 * Calendar-date helpers for YYYY-MM-DD strings, in local time so a date never
 * shifts by a day across time zones.
 */
export function parseDay(day: string): Date {
    const [year, month, date] = day.split('-').map(Number);

    return new Date(year, month - 1, date);
}

export function toDay(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function addDays(day: string, days: number): string {
    const date = parseDay(day);
    date.setDate(date.getDate() + days);

    return toDay(date);
}

export function addMonths(day: string, months: number): string {
    const date = parseDay(day);
    date.setDate(1);
    date.setMonth(date.getMonth() + months);

    return toDay(date);
}

/**
 * Whole days from `from` to `to` (negative when `to` is earlier).
 */
export function daysBetween(from: string, to: string): number {
    return Math.round(
        (parseDay(to).getTime() - parseDay(from).getTime()) / 86_400_000,
    );
}

export function today(): string {
    return toDay(new Date());
}

/**
 * Every day from `from` to `to`, inclusive.
 */
export function eachDay(from: string, to: string): string[] {
    const days: string[] = [];

    for (let day = from; day <= to; day = addDays(day, 1)) {
        days.push(day);
    }

    return days;
}

export function isWeekend(day: string): boolean {
    const weekday = parseDay(day).getDay();

    return weekday === 0 || weekday === 6;
}

const mediumDay = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

/**
 * A YYYY-MM-DD day for reading, such as "20 Nov 2026".
 */
export function formatDay(day: string): string {
    return mediumDay.format(parseDay(day));
}
