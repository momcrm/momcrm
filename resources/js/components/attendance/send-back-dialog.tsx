import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { reject } from '@/routes/timesheets';

/**
 * Sends a timesheet back with a note saying what to fix. The member sees the
 * note in their inbox and can submit it again.
 */
export default function SendBackDialog({
    timesheetId,
    name,
    period,
    onClose,
}: {
    timesheetId: string;
    name: string;
    period: string;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>Send {name}’s timesheet back?</DialogTitle>
                <DialogDescription>
                    {period}. They can fix their time and submit it again.
                </DialogDescription>
                <Form
                    {...reject.form(timesheetId)}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <Textarea
                                name="review_note"
                                aria-label="Note"
                                placeholder="What needs fixing, such as a missing day"
                                rows={3}
                                maxLength={500}
                            />
                            <InputError
                                message={errors.review_note ?? errors.timesheet}
                            />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={onClose}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Send back
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
