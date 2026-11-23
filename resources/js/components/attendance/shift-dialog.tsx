import { Form } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/shifts';
import type { AttendanceMember, ShiftSummary } from '@/types';

type BreakRow = { key: number; started_at: string; ended_at: string };

let nextKey = 0;

/**
 * Adds a shift a member forgot to clock, or corrects one: its times, note and
 * breaks. Times are in the member's timezone. Owners and admins only.
 */
export default function ShiftDialog({
    open,
    onOpenChange,
    member,
    shift,
    defaultDate,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    member: AttendanceMember;
    shift?: ShiftSummary;
    /** The day a new shift starts on, as YYYY-MM-DD. */
    defaultDate: string;
}) {
    const [breaks, setBreaks] = useState<BreakRow[]>(() =>
        (shift?.breaks ?? []).map((item) => ({
            key: nextKey++,
            started_at: item.started_local,
            ended_at: item.ended_local ?? '',
        })),
    );

    const changeBreak = (
        key: number,
        field: 'started_at' | 'ended_at',
        value: string,
    ) =>
        setBreaks((rows) =>
            rows.map((row) =>
                row.key === key ? { ...row, [field]: value } : row,
            ),
        );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>
                    {shift ? 'Correct shift' : `Add a shift for ${member.name}`}
                </DialogTitle>
                <DialogDescription>
                    Times are in {member.name}’s timezone ({member.timezone}).
                    The audit log keeps every change.
                </DialogDescription>
                <Form
                    {...(shift ? update.form(shift.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            {!shift && (
                                <input
                                    type="hidden"
                                    name="user_id"
                                    value={member.id}
                                />
                            )}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField
                                    id="shift-in"
                                    label="Clocked in"
                                    error={errors.clocked_in_at}
                                >
                                    <Input
                                        id="shift-in"
                                        name="clocked_in_at"
                                        type="datetime-local"
                                        defaultValue={
                                            shift?.clocked_in_local ??
                                            `${defaultDate}T09:00`
                                        }
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="shift-out"
                                    label="Clocked out"
                                    error={errors.clocked_out_at}
                                >
                                    <Input
                                        id="shift-out"
                                        name="clocked_out_at"
                                        type="datetime-local"
                                        defaultValue={
                                            shift
                                                ? (shift.clocked_out_local ??
                                                  '')
                                                : `${defaultDate}T17:00`
                                        }
                                    />
                                </FormField>
                            </div>

                            <fieldset className="space-y-2">
                                <legend className="mb-2 text-sm font-medium">
                                    Breaks
                                </legend>
                                {breaks.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        No breaks.
                                    </p>
                                )}
                                {breaks.map((row, index) => (
                                    <div key={row.key} className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Input
                                                aria-label={`Break ${index + 1} start`}
                                                name={`breaks[${index}][started_at]`}
                                                type="datetime-local"
                                                value={row.started_at}
                                                onChange={(event) =>
                                                    changeBreak(
                                                        row.key,
                                                        'started_at',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <span className="text-muted-foreground">
                                                –
                                            </span>
                                            <Input
                                                aria-label={`Break ${index + 1} end`}
                                                name={`breaks[${index}][ended_at]`}
                                                type="datetime-local"
                                                value={row.ended_at}
                                                onChange={(event) =>
                                                    changeBreak(
                                                        row.key,
                                                        'ended_at',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="shrink-0"
                                                aria-label={`Remove break ${index + 1}`}
                                                onClick={() =>
                                                    setBreaks((rows) =>
                                                        rows.filter(
                                                            (other) =>
                                                                other.key !==
                                                                row.key,
                                                        ),
                                                    )
                                                }
                                            >
                                                <X />
                                            </Button>
                                        </div>
                                        <InputError
                                            message={
                                                errors[
                                                    `breaks.${index}.started_at`
                                                ] ??
                                                errors[
                                                    `breaks.${index}.ended_at`
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setBreaks((rows) => [
                                            ...rows,
                                            {
                                                key: nextKey++,
                                                started_at: `${shift?.work_date ?? defaultDate}T12:00`,
                                                ended_at: `${shift?.work_date ?? defaultDate}T12:30`,
                                            },
                                        ])
                                    }
                                >
                                    <Plus />
                                    Add break
                                </Button>
                            </fieldset>

                            <FormField
                                id="shift-note"
                                label="Note"
                                error={errors.note}
                            >
                                <Input
                                    id="shift-note"
                                    name="note"
                                    defaultValue={shift?.note ?? ''}
                                    placeholder="Why it was changed"
                                    maxLength={255}
                                />
                            </FormField>
                            <InputError message={errors.user_id} />
                            <DialogFooter>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {shift ? 'Save shift' : 'Add shift'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
