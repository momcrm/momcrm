import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatDuration, scheduledMinutes, weekdays } from '@/lib/attendance';
import { store, update } from '@/routes/work-schedules';
import type { WeekdayKey, WorkScheduleDay, WorkScheduleSummary } from '@/types';

type Week = Record<WeekdayKey, WorkScheduleDay | null>;

const nineToFive: WorkScheduleDay = {
    starts_at: '09:00',
    ends_at: '17:00',
    break_minutes: 30,
};

const officeWeek: Week = {
    mon: nineToFive,
    tue: nineToFive,
    wed: nineToFive,
    thu: nineToFive,
    fri: nineToFive,
    sat: null,
    sun: null,
};

/**
 * Adds a work schedule or changes one: each weekday's start, end and unpaid
 * break, or a day off, and the grace before someone counts as late.
 */
export default function WorkScheduleDialog({
    open,
    onOpenChange,
    schedule,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    schedule?: WorkScheduleSummary;
}) {
    const [week, setWeek] = useState<Week>(() => schedule?.days ?? officeWeek);
    // Hours a day had before it was switched off, so switching back keeps them.
    const [remembered, setRemembered] = useState<Week>(week);

    const toggle = (key: WeekdayKey, working: boolean) => {
        if (!working) {
            setRemembered((days) => ({ ...days, [key]: week[key] }));
        }

        setWeek((days) => ({
            ...days,
            [key]: working ? (remembered[key] ?? nineToFive) : null,
        }));
    };

    const change = (
        key: WeekdayKey,
        field: keyof WorkScheduleDay,
        value: string,
    ) =>
        setWeek((days) => {
            const day = days[key] ?? nineToFive;

            return {
                ...days,
                [key]: {
                    ...day,
                    [field]:
                        field === 'break_minutes' ? Number(value) || 0 : value,
                },
            };
        });

    const weeklyMinutes = weekdays.reduce((total, { key }) => {
        const day = week[key];

        return total + (day ? scheduledMinutes(day) : 0);
    }, 0);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogTitle>
                    {schedule ? `Edit ${schedule.name}` : 'Add a schedule'}
                </DialogTitle>
                <DialogDescription>
                    Times are each member’s own local time. An end earlier than
                    the start runs past midnight.
                </DialogDescription>
                <Form
                    {...(schedule ? update.form(schedule.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                                <FormField
                                    id="schedule-name"
                                    label="Name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="schedule-name"
                                        name="name"
                                        defaultValue={schedule?.name ?? ''}
                                        placeholder="Office hours"
                                        maxLength={100}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="schedule-grace"
                                    label="Grace (minutes)"
                                    error={errors.grace_minutes}
                                >
                                    <Input
                                        id="schedule-grace"
                                        name="grace_minutes"
                                        type="number"
                                        min={0}
                                        max={120}
                                        defaultValue={
                                            schedule?.grace_minutes ?? 5
                                        }
                                        required
                                    />
                                </FormField>
                            </div>

                            <fieldset className="space-y-2">
                                <legend className="mb-1 flex w-full items-baseline justify-between text-sm font-medium">
                                    Hours
                                    <span className="text-xs font-normal text-muted-foreground">
                                        {formatDuration(weeklyMinutes * 60)} a
                                        week
                                    </span>
                                </legend>
                                <div className="hidden grid-cols-[7rem_1fr_1fr_5.5rem] gap-2 px-1 text-xs text-muted-foreground sm:grid">
                                    <span />
                                    <span>Start</span>
                                    <span>End</span>
                                    <span>Break (min)</span>
                                </div>
                                {weekdays.map(({ key, long }) => {
                                    const day = week[key];
                                    const dayErrors = [
                                        errors[`days.${key}.starts_at`],
                                        errors[`days.${key}.ends_at`],
                                        errors[`days.${key}.break_minutes`],
                                    ].filter(Boolean);

                                    return (
                                        <div key={key} className="space-y-1">
                                            <div className="grid grid-cols-[7rem_1fr_1fr_5.5rem] items-center gap-2">
                                                <label className="flex items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={day !== null}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggle(
                                                                key,
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                    />
                                                    {long}
                                                </label>
                                                {day ? (
                                                    <>
                                                        <Input
                                                            aria-label={`${long} start`}
                                                            name={`days[${key}][starts_at]`}
                                                            type="time"
                                                            value={
                                                                day.starts_at
                                                            }
                                                            onChange={(event) =>
                                                                change(
                                                                    key,
                                                                    'starts_at',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            required
                                                        />
                                                        <Input
                                                            aria-label={`${long} end`}
                                                            name={`days[${key}][ends_at]`}
                                                            type="time"
                                                            value={day.ends_at}
                                                            onChange={(event) =>
                                                                change(
                                                                    key,
                                                                    'ends_at',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            required
                                                        />
                                                        <Input
                                                            aria-label={`${long} break in minutes`}
                                                            name={`days[${key}][break_minutes]`}
                                                            type="number"
                                                            min={0}
                                                            max={240}
                                                            value={
                                                                day.break_minutes
                                                            }
                                                            onChange={(event) =>
                                                                change(
                                                                    key,
                                                                    'break_minutes',
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                    </>
                                                ) : (
                                                    <span className="col-span-3 text-sm text-muted-foreground">
                                                        Day off
                                                    </span>
                                                )}
                                            </div>
                                            {dayErrors.map((message) => (
                                                <InputError
                                                    key={message}
                                                    message={message}
                                                />
                                            ))}
                                        </div>
                                    );
                                })}
                                <InputError message={errors.days} />
                            </fieldset>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {schedule ? 'Save' : 'Add schedule'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
