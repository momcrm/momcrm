import { EyeOff, RotateCcw, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    deleteLines,
    hideLines,
} from '@/components/bank-feeds/bank-feed-table';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';

const barButton =
    'flex h-[26px] items-center gap-1.5 rounded-md px-2 opacity-85 transition hover:bg-background/15 hover:opacity-100 disabled:opacity-50';

/**
 * The dark bar that floats at the foot of the bank feed once lines are
 * picked: hide them, bring them back, or delete them.
 */
export function BankFeedBulkBar({ selection }: { selection: TicketSelection }) {
    const [isConfirmingDelete, setIsConfirmingDelete] = useState(false);
    const count = selection.ids.length;

    useEffect(() => {
        if (count === 0) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (
                event.key === 'Escape' &&
                !document.querySelector('[role="dialog"]')
            ) {
                selection.clear();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [count, selection]);

    if (count === 0) {
        return null;
    }

    return (
        <>
            <div
                role="toolbar"
                aria-label="Bulk actions"
                className="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 animate-in items-center gap-1 rounded-[10px] bg-foreground py-1.5 pr-1.5 pl-3.5 text-[12.5px] text-background shadow-pop duration-300 fade-in slide-in-from-bottom-2"
            >
                <span className="font-semibold whitespace-nowrap tabular-nums">
                    {count} selected
                </span>
                <span className="mx-1 h-[18px] w-px bg-background/20" />
                <button
                    type="button"
                    className={barButton}
                    onClick={() =>
                        hideLines(selection.ids, true, selection.clear)
                    }
                >
                    <EyeOff className="size-3.5" />
                    Hide
                </button>
                <button
                    type="button"
                    className={barButton}
                    onClick={() =>
                        hideLines(selection.ids, false, selection.clear)
                    }
                >
                    <RotateCcw className="size-3.5" />
                    Pending
                </button>
                <button
                    type="button"
                    className={barButton}
                    onClick={() => setIsConfirmingDelete(true)}
                >
                    <Trash2 className="size-3.5" />
                    Delete
                </button>
                <button
                    type="button"
                    aria-label="Clear selection"
                    onClick={selection.clear}
                    className="ml-0.5 flex size-[26px] items-center justify-center rounded-md opacity-85 transition hover:bg-background/15 hover:opacity-100"
                >
                    <X className="size-3.5" />
                </button>
            </div>

            <Dialog
                open={isConfirmingDelete}
                onOpenChange={setIsConfirmingDelete}
            >
                <DialogContent>
                    <DialogTitle>
                        Delete {count} {count === 1 ? 'line' : 'lines'}?
                    </DialogTitle>
                    <DialogDescription>
                        Importing the same statement again brings them back. To
                        keep a line out of the way for good, hide it instead.
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                deleteLines(selection.ids, () => {
                                    setIsConfirmingDelete(false);
                                    selection.clear();
                                })
                            }
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
