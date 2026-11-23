/** Whether the signed-in member is clocked in or on a break, shared with every page. */
export type AttendanceClock = {
    shift_id: string | null;
    clocked_in_at: string | null;
    on_break_since: string | null;
    /** Seconds spent on breaks this shift that have already ended. */
    break_seconds: number;
};

export type AttendanceStatus = 'in' | 'break' | 'out' | 'not_in';

/** A member on the Today board. */
export type AttendanceBoardRow = {
    id: string;
    name: string;
    timezone: string;
    status: AttendanceStatus;
    first_in_at: string | null;
    clocked_in_at: string | null;
    last_out_at: string | null;
    on_break_since: string | null;
    worked_seconds: number;
};

export type ShiftBreakSummary = {
    id: string;
    started_at: string;
    ended_at: string | null;
    /** Wall-clock time in the member's timezone, as YYYY-MM-DDTHH:mm. */
    started_local: string;
    ended_local: string | null;
};

export type ShiftSummary = {
    id: string;
    user_id: string;
    work_date: string;
    clocked_in_at: string;
    clocked_out_at: string | null;
    /** Wall-clock time in the member's timezone, as YYYY-MM-DDTHH:mm. */
    clocked_in_local: string;
    clocked_out_local: string | null;
    note: string | null;
    is_open: boolean;
    on_break_since: string | null;
    worked_seconds: number;
    break_seconds: number;
    breaks: ShiftBreakSummary[];
};

export type AttendanceDay = {
    date: string;
    worked_seconds: number;
    break_seconds: number;
    shifts: ShiftSummary[];
};

export type AttendanceWeek = {
    starts_on: string;
    ends_on: string;
    worked_seconds: number;
    break_seconds: number;
    days: AttendanceDay[];
};

export type AttendanceMember = {
    id: string;
    name: string;
    timezone: string;
};
