/** Whether the signed-in member is clocked in or on a break, shared with every page. */
export type AttendanceClock = {
    shift_id: string | null;
    clocked_in_at: string | null;
    on_break_since: string | null;
    /** Seconds spent on breaks this shift that have already ended. */
    break_seconds: number;
};

export type AttendanceStatus = 'in' | 'break' | 'out' | 'not_in' | 'off';

/** How a day went against the member's schedule. */
export type AttendanceDayStatus =
    | 'present'
    | 'late'
    | 'left_early'
    | 'absent'
    | 'upcoming'
    | 'holiday'
    | 'day_off'
    | 'on_leave'
    | 'unscheduled';

export type AttendanceFlag = 'late' | 'left_early' | 'absent' | 'overtime';

/** A day measured against the member's schedule (AttendanceDay on the server). */
export type AttendanceDayResult = {
    status: AttendanceDayStatus;
    flags: AttendanceFlag[];
    holiday: string | null;
    /** The leave taken that day, such as "Annual leave (morning)". */
    leave: string | null;
    expected_starts_at: string | null;
    expected_ends_at: string | null;
    expected_seconds: number;
    worked_seconds: number;
    break_seconds: number;
    overtime_seconds: number;
    late_seconds: number;
};

/** A member on the Today board. */
export type AttendanceBoardRow = {
    id: string;
    name: string;
    timezone: string;
    work_schedule_id: string | null;
    /** The member's own today, in their timezone. */
    work_date: string;
    status: AttendanceStatus;
    first_in_at: string | null;
    clocked_in_at: string | null;
    last_out_at: string | null;
    on_break_since: string | null;
    worked_seconds: number;
    /** Why they're off today, for everyone to see. */
    away: 'day_off' | 'holiday' | 'on_leave' | null;
    holiday: string | null;
    /** How their day is going; only for owners and admins, or yourself. */
    day: AttendanceDayResult | null;
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

export type AttendanceDay = AttendanceDayResult & {
    date: string;
    shifts: ShiftSummary[];
};

export type AttendanceWeek = {
    starts_on: string;
    ends_on: string;
    worked_seconds: number;
    break_seconds: number;
    expected_seconds: number;
    overtime_seconds: number;
    days: AttendanceDay[];
};

export type AttendanceMember = {
    id: string;
    name: string;
    timezone: string;
    work_schedule_id: string | null;
};

export type WeekdayKey = 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat' | 'sun';

/** One weekday's hours; an end before the start runs past midnight. */
export type WorkScheduleDay = {
    starts_at: string;
    ends_at: string;
    break_minutes: number;
};

export type WorkScheduleSummary = {
    id: string;
    name: string;
    is_default: boolean;
    grace_minutes: number;
    days: Record<WeekdayKey, WorkScheduleDay | null>;
};

export type HolidaySummary = {
    id: string;
    date: string;
    name: string;
};

export type LeaveStatus = 'pending' | 'approved' | 'declined' | 'cancelled';

export type LeavePart = 'full' | 'morning' | 'afternoon';

/** A leave request on the Leave page. */
export type LeaveSummary = {
    id: string;
    user_id: string;
    user_name: string;
    leave_type_id: string;
    leave_type: string;
    hue: number | null;
    starts_on: string;
    ends_on: string;
    part: LeavePart;
    /** Working days it takes, or null when there's no schedule to count. */
    days: number | null;
    status: LeaveStatus;
    reason: string | null;
    reviewed_by_name: string | null;
    reviewed_at: string | null;
    review_note: string | null;
    created_at: string | null;
    can_cancel: boolean;
};

/** Approved leave on the calendar. The kind shows only to those allowed. */
export type LeaveCalendarEntry = {
    id: string;
    user_id: string;
    name: string;
    leave_type: string | null;
    hue: number | null;
    starts_on: string;
    ends_on: string;
    part: LeavePart;
};
