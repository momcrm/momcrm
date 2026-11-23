import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Action = { action: string; method: 'post' | 'delete' };

/**
 * A delete button that asks for confirmation in a dialog first. Pass
 * `trigger={null}` with `open` and `onOpenChange` to open it from elsewhere,
 * such as a "…" menu.
 */
export default function ConfirmDelete({
    action,
    title,
    description,
    trigger = 'Delete',
    confirmLabel = 'Delete',
    open,
    onOpenChange,
}: {
    action: Action;
    title: string;
    description: ReactNode;
    trigger?: string | null;
    confirmLabel?: string;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger !== null && (
                <DialogTrigger asChild>
                    <Button variant="outline">{trigger}</Button>
                </DialogTrigger>
            )}
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <Form {...action}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                disabled={processing}
                                data-test="confirm-delete-button"
                            >
                                {confirmLabel}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
