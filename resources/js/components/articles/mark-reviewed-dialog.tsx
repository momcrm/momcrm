import { Form } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { review } from '@/routes/articles';

/** Six months from today, as YYYY-MM-DD in the browser's time zone. */
function sixMonthsOn(): string {
    const day = new Date();
    day.setMonth(day.getMonth() + 6);

    return [
        day.getFullYear(),
        String(day.getMonth() + 1).padStart(2, '0'),
        String(day.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * Says an article was checked today and is still right, and when to check
 * it next.
 */
export default function MarkReviewedDialog({
    articleId,
}: {
    articleId: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <CalendarCheck />
                    Mark reviewed
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-sm">
                <DialogTitle>Mark as reviewed</DialogTitle>
                <DialogDescription>
                    You checked it today and it is still right. It doesn't make
                    a new version.
                </DialogDescription>
                <Form
                    {...review.form(articleId)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="next-review-on"
                                label="Review again on"
                                hint="Leave it empty to stop reminding."
                                error={errors.next_review_on}
                            >
                                <Input
                                    id="next-review-on"
                                    name="next_review_on"
                                    type="date"
                                    defaultValue={sixMonthsOn()}
                                />
                            </FormField>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    Mark reviewed
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
