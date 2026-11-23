import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
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
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/leave-requests';
import type { LeavePart } from '@/types';

/**
 * Asks for time off: its type, first and last day, and for one day, whether
 * a morning or afternoon only. Given `members`, an owner or admin records
 * leave for one of them instead, which is approved straight away.
 */
export default function LeaveDialog({
    open,
    onOpenChange,
    today,
    members,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    today: string;
    members?: { id: string; name: string }[];
}) {
    const types = (usePage().props.choices?.leave_type ?? []).filter(
        (type) => !type.is_archived,
    );
    const [startsOn, setStartsOn] = useState(today);
    const [endsOn, setEndsOn] = useState(today);
    const [part, setPart] = useState<LeavePart>('full');
    const isRecording = members !== undefined;
    const oneDay = startsOn === endsOn;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {isRecording ? 'Record leave' : 'Request leave'}
                </DialogTitle>
                <DialogDescription>
                    {isRecording
                        ? 'For someone who is away, such as a sick day phoned in. It’s approved straight away.'
                        : 'An owner or admin will approve it. You’ll hear back in your inbox.'}
                </DialogDescription>
                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            {isRecording && (
                                <FormField
                                    id="leave-member"
                                    label="Who"
                                    error={errors.user_id}
                                >
                                    <NativeSelect
                                        id="leave-member"
                                        name="user_id"
                                        required
                                    >
                                        {members.map((member) => (
                                            <option
                                                key={member.id}
                                                value={member.id}
                                            >
                                                {member.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}
                            <FormField
                                id="leave-type"
                                label="Type of leave"
                                error={errors.leave_type_id}
                            >
                                <NativeSelect
                                    id="leave-type"
                                    name="leave_type_id"
                                    defaultValue={
                                        types.find((type) => type.is_default)
                                            ?.id
                                    }
                                    required
                                >
                                    {types.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <div className="grid grid-cols-2 gap-3">
                                <FormField
                                    id="leave-starts"
                                    label="First day"
                                    error={errors.starts_on}
                                >
                                    <Input
                                        id="leave-starts"
                                        name="starts_on"
                                        type="date"
                                        value={startsOn}
                                        onChange={(event) => {
                                            setStartsOn(event.target.value);

                                            if (event.target.value > endsOn) {
                                                setEndsOn(event.target.value);
                                            }
                                        }}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="leave-ends"
                                    label="Last day"
                                    error={errors.ends_on}
                                >
                                    <Input
                                        id="leave-ends"
                                        name="ends_on"
                                        type="date"
                                        min={startsOn}
                                        value={endsOn}
                                        onChange={(event) =>
                                            setEndsOn(event.target.value)
                                        }
                                        required
                                    />
                                </FormField>
                            </div>
                            {oneDay ? (
                                <FormField
                                    id="leave-part"
                                    label="How much of the day"
                                    error={errors.part}
                                >
                                    <NativeSelect
                                        id="leave-part"
                                        name="part"
                                        value={part}
                                        onChange={(event) =>
                                            setPart(
                                                event.target.value as LeavePart,
                                            )
                                        }
                                    >
                                        <option value="full">Full day</option>
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">
                                            Afternoon
                                        </option>
                                    </NativeSelect>
                                </FormField>
                            ) : (
                                <input type="hidden" name="part" value="full" />
                            )}
                            <FormField
                                id="leave-reason"
                                label="Note (optional)"
                                error={errors.reason}
                            >
                                <Textarea
                                    id="leave-reason"
                                    name="reason"
                                    rows={2}
                                    maxLength={1000}
                                />
                            </FormField>
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
                                    {isRecording ? 'Record leave' : 'Request'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
